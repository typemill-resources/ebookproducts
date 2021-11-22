<?php

namespace Plugins\ebookproducts;

use \Typemill\Plugin;
use Typemill\Models\WriteYaml;


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
		$this->addSvgSymbol('<symbol id="icon-undo" viewBox="0 0 32 32"><path d="M16 2c-4.418 0-8.418 1.791-11.313 4.687l-4.686-4.687v12h12l-4.485-4.485c2.172-2.172 5.172-3.515 8.485-3.515 6.627 0 12 5.373 12 12 0 3.584-1.572 6.801-4.063 9l2.646 3c3.322-2.932 5.417-7.221 5.417-12 0-8.837-7.163-16-16-16z"></path></symbol>');

		$navi = $navidata->getData();

		$navi['ebookproducts'] 		= ['routename' => 'ebookproducts.show', 'icon' => 'icon-undo', 'aclresource' => 'system', 'aclprivilege' => 'view'];

		# set the navigation item active
		if(trim($this->getPath(),"/") == 'tm/ebookproducts')
		{
			$navi['ebookproducts']['active'] = true;
		}

		$navidata->setData($navi);
	}
	
	public function onShortcodeFound($shortcode)
	{
		$shortcodeArray = $shortcode->getData();

		if($shortcodeArray['name'] == 'ebookproduct' && isset($shortcodeArray['params']['id']))
		{
	        $this->addCSS('/ebookproducts/css/style.css');

			$id 			= $shortcodeArray['params']['id'];
			$settings 		= $this->getSettings();
			$html 			= '<p>We did not find a product</p>';
			$folderName 	= 'data' . DIRECTORY_SEPARATOR . 'ebookproducts';
			$folder 		= $settings['rootPath'] . $folderName;

			if(!$this->checkEbookFolder($folder))
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
						$html .= '<a class="button" href="' . $base_url . '/' . $downloadurl1 . '">' . $downloadlabel1 . '</a>';
					}
					else
					{
						# user has no access
						$noaccess = true;
					}
				}
				else
				{
					# the file is not restricted
					$html .= '<a class="button" href="' . $base_url . '/'  . $downloadurl1 . '">' . $downloadlabel1 . '</a>';
				}

				if($restrictions && $downloadurl2 && isset($restrictions[$downloadurl2]))
				{
					# the second download is restricted
					if(isset($_SESSION['role']) && ($_SESSION['role'] == 'administrator' OR $_SESSION['role'] == $restrictions[$downloadurl1] OR $this->container->acl->inheritsRole($_SESSION['role'], $restrictions[$downloadurl2])) )
					{
						# user is allowed to download the file
						$html .= '<a class="button" href="' . $base_url . '/'  . $downloadurl2 . '">' . $downloadlabel2 . '</a>';
					}
					else
					{
						# user has no access
						$noaccess = true;
					}
				}
				else
				{
					# the file is not restricted
					$html .= '<a class="button" href="' . $base_url . '/' . $downloadurl2 . '">' . $downloadlabel2 . '</a>';
				}

				# if user has no access to one or more download-files
				if($noaccess)
				{
					if($noaccessurl1)
					{ 
						$html .= '<a class="button" href="' . $noaccessurl1 . '">' . $noaccesslabel1 . '</a>';
					}
					if($noaccessurl2)
					{ 
						$html .= '<a class="button" href="' . $noaccessurl2 . '">' . $noaccesslabel2 . '</a>';
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

		if(!$this->checkEbookFolder($folder))
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

		if(!$this->checkEbookFolder($folder))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'Please make sure that the folder data/ebooks exists and is writable.']), 500);
		}

		# create objects to read and write data
		$writeYaml 		= new WriteYaml();

#		$validatedParams = $this->validateEbookData($params, $writeYaml);

#		if(isset($validatedParams['errors']))
#		{
#			return $response->withJson(array('data' => false, 'errors' => $validatedParams['errors']), 422);
#		}
		
		# write params
		$ebookproducts 	= $writeYaml->updateYaml($folderName, 'ebookproducts.yaml', $params['ebookproducts']);
		
		if($ebookproducts)
		{
			return $response->withJson(array("ebookproducts" => $ebookproducts), 200);
		}

		return $response->withJson(array('data' => false, 'errors' => ['message' => 'We could not store all data. Please try again.']), 500);
	}

	private function checkEbookFolder($folder)
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