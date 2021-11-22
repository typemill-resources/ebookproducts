const FormBus = new Vue();

let ebookproducts = new Vue({
	el: "#ebookproducts",
	data: function () {
		return {
			formdata: {},		/* hold all invoice data */
			productform: false,
			currentproduct: '',
			newproductid: '',
			root: document.getElementById("main").dataset.url,
			errors: false,
		}
	},
 	template: '<div class="noclass">' +
				'<div class="mv4">' + 
					'<label for="productid">ID for new product</label>' +
 					'<input name="productid" v-model="newproductid" type="text">' +
 					'<button class="link bn br2 bg-tm-green white absolute dim pointer right-2 pa3" @click.prevent="addProduct()">add product</button>' +
				'</div>' +
 				'<form id="" @submit.prevent="submitstep">' +
					'<div v-if="formdata" v-for="(product,productname) in formdata">' + 
						'<fieldset class="ba b--moon-gray pa3 mb3" @click="currentproduct = productname">' +
							'<legend class="b pa2 f4">ID: {{ productname }}</legend>' + 
							'<component v-for="(fielddefinition, fieldname) in productform"' +
			            	    	' :key="fieldname"' +
			                		' :is="selectComponent(fielddefinition)"' +
			                		' :errors="errors"' +
			                		' :name="fieldname"' +
			                		' v-model="formdata[productname][fieldname]"' +
			                		' v-bind="fielddefinition">' +
							'</component>' + 
					  		'<button @click.prevent="deleteProduct(productname)" class="link bn br1 bg-tm-red white dim ph4 pv2 right mr4">delete product</button>' +
					  	'</fieldset>' +
					'</div>' +
				'</form>' +
				'<button @click.prevent="submit()" class="link w-100 bn br2 pa3 bg-tm-green white dim" type="submit">Save products</button>' +
 			  '</div>',	
	mounted: function(){

		FormBus.$on('forminput', formdata => {
			console.info(this.currentproduct);
			console.info(formdata.name);
			console.info(formdata.value);
			this.$set(this.formdata[this.currentproduct], formdata.name, formdata.value);
		});

		this.productform = {
				title: {
					type: 'text',
					label: 'title of the product',
					class: 'large'
				},
				cover: {
					type: 'image',
					label: 'add a cover image',
				},
				description: {
					type: 'textarea',
					label: 'Description (Markdown)',
					class: 'large'
				},
				downloadlabel1: {
					type: 'text',
					label: 'Label for the first download button',
					class: 'large'
				},
				downloadurl1: {
					type: 'file',
					label: 'First download file',
					class: 'large'
				},
				downloadlabel2: {
					type: 'text',
					label: 'Label for second download-button',
					class: 'large'
				},
				downloadurl2: {
					type: 'file',
					label: 'Second download file',
					class: 'large'
				},
				noaccesslabel1: {
					type: 'text',
					label: 'Alternative link-label for users without access',
					placeholder: 'Subscribe here to download',
					class: 'medium'
				},
				noaccessurl1: {
					type: 'text',
					label: 'Alternative link-url for users without access',
					placeholder: '/tm/plans',
					class: 'medium'
				},
				noaccesslabel2: {
					type: 'text',
					label: 'Alternative link-label for users without access',
					placeholder: 'Buy at amazon',
					class: 'medium'
				},
				noaccessurl2: {
					type: 'text',
					label: 'Alternative link-url for users without access',
					placeholder: 'https://amazon.com/yourbook',
					class: 'medium'
				},
		};

		var self = this;

	   	myaxios.get('/api/v1/ebookproducts',{
	        params: {
				'url':			document.getElementById("path").value,
				'csrf_name': 	document.getElementById("csrf_name").value,
				'csrf_value':	document.getElementById("csrf_value").value,
	        }
		})
	   	.then(function (response) {
	   		if(response.data.formdata)
	   		{
		    	self.formdata = response.data.formdata;
	   		}
	    })
	    .catch(function (error)
	    {
	        if(error.response)
	        {
	        }
	    });
	},
	methods: {
		addProduct: function()
		{
			var newproduct = {[this.newproductid] :  {'title' : '','cover' : '','description' : '','downloadlabel' : '', 'downloadurl' : '','firstbuttonlabel' : '','firstbuttonurl' : '','secondbuttonlabel' : '','secondbuttonurl' : ''  }};
			this.formdata = Object.assign({}, newproduct, this.formdata);
			this.newproductid = '';
		},
		deleteProduct: function(productname)
		{
			this.$delete( this.formdata, productname );
		},
		submit: function()
		{
			var self = this;

		   	myaxios.post('/api/v1/ebookproducts',{
				'url':				document.getElementById("path").value,
				'csrf_name': 		document.getElementById("csrf_name").value,
				'csrf_value':		document.getElementById("csrf_value").value,
				'ebookproducts': 	this.formdata
			})
		   	.then(function (response) {
		   		self.ebookproducts = {};
		   		if(response.data.ebookproducts)
		   		{
			    	self.ebookproducts = response.data.ebookproducts.formdata;
		   		}
		    })
		    .catch(function (error)
		    {
		        if(error.response)
		        {
		        }
		    });
		},
		getFieldClass: function(field)
		{
			if(field.type == 'fieldset' || field.type == 'image')
			{ 
				return; 
			}
			else if(field.class === undefined )
			{
				return 'large';
			}
			else
			{
				var fieldclass = field.class;
				delete field.class;
				return fieldclass;
			}
		},	
		selectComponent: function(field)
		{
			return 'component-'+field.type;
		},		
	}
})