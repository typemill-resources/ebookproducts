<?php

namespace Plugins\ebookproducts;

use \Typemill\Plugin;
use Typemill\Models\WriteYaml;
use Typemill\Models\Validation;


class ebookproducts extends Plugin
{
	protected $settings;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onShortcodeFound' 			=> 'onShortcodeFound',
			'onSystemnaviLoaded'		=> 'onSystemnaviLoaded',
			'onPageReady'				=> 'onPageReady',
			'onTwigLoaded'				=> 'onTwigLoaded',
		);
    }

	# add routes
	public static function addNewRoutes()
	{
		return [
			['httpMethod' => 'get', 'route' => '/api/v1/ebookproducts', 'name' => 'ebookproducts.get', 'class' => 'Plugins\ebookproducts\ebookproducts:getEbookProducts', 'resource' => 'system', 'privilege' => 'view'],
			['httpMethod' => 'post', 'route' => '/api/v1/ebookproducts', 'name' => 'ebookproducts.post', 'class' => 'Plugins\ebookproducts\ebookproducts:storeEbookProducts', 'resource' => 'system', 'privilege' => 'view'],
			['httpMethod' => 'get', 'route' => '/tm/ebookproducts', 'name' => 'ebookproducts.show', 'class' => 'Typemill\Controllers\ControllerSettings:showBlank', 'resource' => 'system', 'privilege' => 'view'],
		];
	}

	# add new navi-items into the admin settings
	public function onSystemnaviLoaded($navidata)
	{
		$this->addSvgSymbol('<symbol id="icon-download" viewBox="0 0 24 24"><path d="M20 15v4c0 0.276-0.111 0.525-0.293 0.707s-0.431 0.293-0.707 0.293h-14c-0.276 0-0.525-0.111-0.707-0.293s-0.293-0.431-0.293-0.707v-4c0-0.552-0.448-1-1-1s-1 0.448-1 1v4c0 0.828 0.337 1.58 0.879 2.121s1.293 0.879 2.121 0.879h14c0.828 0 1.58-0.337 2.121-0.879s0.879-1.293 0.879-2.121v-4c0-0.552-0.448-1-1-1s-1 0.448-1 1zM13 12.586v-9.586c0-0.552-0.448-1-1-1s-1 0.448-1 1v9.586l-3.293-3.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414l5 5c0.092 0.092 0.202 0.166 0.324 0.217s0.253 0.076 0.383 0.076c0.256 0 0.512-0.098 0.707-0.293l5-5c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0z"></path></symbol>');

		$navi = $navidata->getData();

		$navi['Ebookproducts'] 		= ['routename' => 'ebookproducts.show', 'icon' => 'icon-download', 'aclresource' => 'system', 'aclprivilege' => 'view'];

		# set the navigation item active
		if(trim($this->getPath(),"/") == 'tm/ebookproducts')
		{
			$navi['Ebookproducts']['active'] = true;
		}

		$navidata->setData($navi);
	}
	
	public function onShortcodeFound($shortcode)
	{
		$shortcodeArray = $shortcode->getData();

		if(is_array($shortcodeArray) && $shortcodeArray['name'] == 'registershortcode')
		{
			$settings 		= $this->getSettings();
			$folderName 	= 'data' . DIRECTORY_SEPARATOR . 'ebookproducts';
			$folder 		= $settings['rootPath'] . $folderName;
			$writeYaml 		= new WriteYaml();
			$ebookproducts 	= $writeYaml->getYaml($folderName, 'ebookproducts.yaml');
			$content 		= [];

			if($ebookproducts)
			{
				foreach($ebookproducts as $key => $value)
				{
					$content[] = $key;
				}
			}

			$shortcodeArray['data']['ebookproduct'] = [ 'id' => ['content' => $content] ];

			$shortcode->setData($shortcodeArray);
		}

		if(is_array($shortcodeArray) && $shortcodeArray['name'] == 'ebookproduct' && isset($shortcodeArray['params']['id']))
		{
	        $this->addCSS('/ebookproducts/css/style.css');

			$id 			= $shortcodeArray['params']['id'];
			$settings 		= $this->getSettings();
			$html 			= '<p>We did not find a product</p>';
			$folderName 	= 'data' . DIRECTORY_SEPARATOR . 'ebookproducts';
			$folder 		= $settings['rootPath'] . $folderName;

			if(!$this->checkFolder($folder))
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'Please make sure that the folder data/ebookproducts exists and is writable.']), 500);
			}

			$writeYaml 		= new WriteYaml();
			$ebookproducts 	= $writeYaml->getYaml($folderName, 'ebookproducts.yaml');

			if(isset($ebookproducts[$id]))
			{
				# independent from access
				$base_url = $this->container['request']->getUri()->getBaseUrl();

				$html = '<div class="ebookproduct">';
				$html .= '<div class="ebookproductimage"><img src="' . $base_url . '/' . $ebookproducts[$id]['cover'] . '"></div>';
				$html .= '<div class="ebookproducttext">';
				$html .= '<h2>' . $ebookproducts[$id]['title'] . '</h2>'; 
				$html .= '<p>' . $ebookproducts[$id]['description'] . '</p>';

				$downloadlabel1		= ( isset($ebookproducts[$id]['downloadlabel1']) && $ebookproducts[$id]['downloadlabel1'] != '') ? $ebookproducts[$id]['downloadlabel1'] : false;
				$downloadurl1  		= ( isset($ebookproducts[$id]['downloadurl1']) && $ebookproducts[$id]['downloadurl1'] != '' ) ? $ebookproducts[$id]['downloadurl1'] : false;
				$downloadlabel2 	= ( isset($ebookproducts[$id]['downloadlabel2']) && $ebookproducts[$id]['downloadlabel2'] != '' ) ? $ebookproducts[$id]['downloadlabel2'] : false;
				$downloadurl2  		= ( isset($ebookproducts[$id]['downloadurl2']) && $ebookproducts[$id]['downloadurl2'] != '' ) ? $ebookproducts[$id]['downloadurl2'] : false;
				$noaccesslabel1 	= ( isset($ebookproducts[$id]['noaccesslabel1']) && $ebookproducts[$id]['noaccesslabel1'] != '' ) ? $ebookproducts[$id]['noaccesslabel1'] : false;
				$noaccessurl1 		= ( isset($ebookproducts[$id]['noaccessurl1']) && $ebookproducts[$id]['noaccessurl1'] != '' ) ? $ebookproducts[$id]['noaccessurl1'] : false; 
				$noaccesslabel2 	= ( isset($ebookproducts[$id]['noaccesslabel2']) && $ebookproducts[$id]['noaccesslabel2'] != '' ) ? $ebookproducts[$id]['noaccesslabel2'] : false;
				$noaccessurl2 		= ( isset($ebookproducts[$id]['noaccessurl2']) && $ebookproducts[$id]['noaccessurl2'] != '' ) ? $ebookproducts[$id]['noaccessurl2'] : false;

				$restrictions 		= $writeYaml->getYaml('media' . DIRECTORY_SEPARATOR . 'files', 'filerestrictions.yaml');
				$noaccess 			= false;

				$html .= '<div class="ebookproductaction">';
				if($restrictions && $downloadurl1 && isset($restrictions[$downloadurl1]))
				{
					# the first download is restricted
					if(isset($_SESSION['role']) && ($_SESSION['role'] == 'administrator' OR $_SESSION['role'] == $restrictions[$downloadurl1] OR $this->container->acl->inheritsRole($_SESSION['role'], $restrictions[$downloadurl1])) )
					{
						# user is allowed to download the file
						$html .= '<a download class="button" href="' . $base_url . '/' . $downloadurl1 . '">' . $downloadlabel1 . '</a>';
					}
					else
					{
						# user has no access
						$noaccess = true;
					}
				}
				elseif($downloadurl1)
				{
					# the file is not restricted
					$html .= '<a download class="button" href="' . $base_url . '/'  . $downloadurl1 . '">' . $downloadlabel1 . '</a>';
				}

				if($restrictions && $downloadurl2 && isset($restrictions[$downloadurl2]))
				{
					# the second download is restricted
					if(isset($_SESSION['role']) && ($_SESSION['role'] == 'administrator' OR $_SESSION['role'] == $restrictions[$downloadurl1] OR $this->container->acl->inheritsRole($_SESSION['role'], $restrictions[$downloadurl2])) )
					{
						# user is allowed to download the file
						$html .= '<a download class="button" href="' . $base_url . '/'  . $downloadurl2 . '">' . $downloadlabel2 . '</a>';
					}
					else
					{
						# user has no access
						$noaccess = true;
					}
				}
				elseif($downloadurl2)
				{
					# the file is not restricted
					$html .= '<a download class="button" href="' . $base_url . '/' . $downloadurl2 . '">' . $downloadlabel2 . '</a>';
				}

				# if user has no access to one or more download-files
				if($noaccess)
				{
					if($noaccessurl1)
					{ 
						$html .= '<a download class="button" href="' . $noaccessurl1 . '">' . $noaccesslabel1 . '</a>';
					}
					if($noaccessurl2)
					{ 
						$html .= '<a download class="button" href="' . $noaccessurl2 . '">' . $noaccesslabel2 . '</a>';
					}
				}

				$html .= '</div></div></div>'; 
			}

			$shortcode->setData($html);
		}
	}

	public function onTwigLoaded($data)
	{
		$this->addEditorCSS('/ebookproducts/css/style.css');
	}

	# show subscriberlist in admin area
	public function onPageReady($data)
	{
		# admin stuff
		if($this->adminpath && $this->path == 'tm/ebookproducts')
		{
			$this->addJS('/ebookproducts/js/vue-ebookproducts.js');

			$pagedata = $data->getData();

			$twig 	= $this->getTwig();
			$loader = $twig->getLoader();
			$loader->addPath(__DIR__ . '/templates');
				
			# fetch the template and render it with twig
			$content = $twig->fetch('/ebookproducts.twig', []);

			$pagedata['content'] = $content;

			$data->setData($pagedata);
		}
	}

	# gets the centrally stored ebook-data for ebook-plugin in settings-area
	public function getEbookProducts($request, $response, $args)
	{
		$settings 		= $this->getSettings();

		$folderName 	= 'data' . DIRECTORY_SEPARATOR . 'ebookproducts';
		$folder 		= $settings['rootPath'] . $folderName;

		if(!$this->checkFolder($folder))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'Please make sure that the folder data/ebookproducts exists and is writable.']), 500);
		}

		# write params
		$writeYaml 	= new WriteYaml();

		# get the stored ebook-data
		$formdata = $writeYaml->getYaml($folderName, 'ebookproducts.yaml');

		return $response->withJson(array('formdata' => $formdata, 'errors' => false), 200);
	}

	# stores the ebook-data (book-details and navigation) from central ebook in settings-area into the data-folder 
	public function storeEbookProducts($request, $response, $args)
	{
		$params 		= $request->getParams();
		$settings 		= $this->getSettings();
		$uri 			= $request->getUri()->withUserInfo('');
		$base_url		= $uri->getBaseUrl();

		$folderName 	= 'data' . DIRECTORY_SEPARATOR . 'ebookproducts';
		$folder 		= $settings['rootPath'] . $folderName;

		if(!$this->checkFolder($folder))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'Please make sure that the folder data/ebooks exists and is writable.']), 500);
		}

		# create objects to read and write data
		$writeYaml 		= new WriteYaml();

		$validationErrors = $this->validateData($params);

		if(!empty($validationErrors))
		{
			$errors = [];
			# prepare error data for vue frontend 
			foreach($params['ebookproducts'] as $id => $productdata)
			{
				$errors[$id] = false;
				if(isset($validationErrors[$id]))
				{
					foreach($validationErrors[$id] as $fieldname => $errormessages)
					{
						# only use the first error message for each field
						$errors[$id][$fieldname] = $errormessages[0];
					}
				}
			}
			return $response->withJson($errors, 422);
		}
		
		# write params
		$ebookproducts 	= $writeYaml->updateYaml($folderName, 'ebookproducts.yaml', $params['ebookproducts']);
		
		if($ebookproducts)
		{
			return $response->withJson(array("ebookproducts" => $ebookproducts), 200);
		}

		return $response->withJson(array('data' => false, 'errors' => ['message' => 'We could not store all data. Please try again.']), 500);
	}

	private function validateData($params)
	{
		$ebookproducts	= isset($params['ebookproducts']) ? $params['ebookproducts'] : false;
		$errors			= [];

		# create validation object
		$validation	= new Validation();

		foreach($ebookproducts as $id => $productdata)
		{
	
			# return standard valitron object for standardfields
			$v = $validation->returnValidator($productdata);

			$v->rule('noHTML', 'title');
			$v->rule('lengthMax', 'title',200);
			$v->rule('noHTML', 'cover');
			$v->rule('lengthMax', 'cover',200);
			$v->rule('noHTML', 'description');
			$v->rule('lengthMax', 'description',2000);
			$v->rule('noHTML', 'downloadlabel1');
			$v->rule('lengthMax', 'downloadlabel1',50);
			$v->rule('noHTML', 'downloadurl1');
			$v->rule('lengthMax', 'downloadurl1',200);
			$v->rule('noHTML', 'downloadlabel2');
			$v->rule('lengthMax', 'downloadlabel2',50);
			$v->rule('noHTML', 'downloadurl2');
			$v->rule('lengthMax', 'downloadurl2',200);
			$v->rule('noHTML', 'noaccesslabel1');
			$v->rule('lengthMax', 'noaccesslabel1',50);
			$v->rule('noHTML', 'noaccessurl1');
			$v->rule('lengthMax', 'noaccessurl1',200);
			$v->rule('noHTML', 'noaccesslabel2');
			$v->rule('lengthMax', 'noaccesslabel2',50);
			$v->rule('noHTML', 'noaccessurl2');
			$v->rule('lengthMax', 'noaccessurl2',200);

			if(!$v->validate())
			{
				$errors[$id] = $v->errors();
			}
		}

		return $errors;
	}

	private function checkFolder($folder)
	{

		if(!file_exists($folder) && !is_dir( $folder ))
		{
			if(!mkdir($folder, 0755, true))
			{
				return false;
			}
		}
		elseif(!is_writeable($folder) OR !is_readable($folder))
		{
			return false;
		}

		return true;
	}
}