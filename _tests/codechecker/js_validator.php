<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2009

 See text/en/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_validation
 */

/*
No support for:
 - new('foo'). / new('foo')[	REASON: no good reason to do it really
 - (function foo())(). / (function foo())()[	REASON: no good reason to do it really
 - if...(then)...catch...else	REASON: seen it, doubt it's valid
 - Full DOM	REASON: not implemented on any browser. If however, conditional testing is used, the validator will let it pass
 - (Most) HTML attributes hardcoded into DOM objects	REASON: no reason to do it, and less compatible and clear than getAttribute
 - ";" insertion	REASON: very sloppy
 - checking against argument types	REASON: Javascript extension, but we could do. Not a lot of advantage, quite a lot of work
 - checking for locked	REASON: Javascript extension, but we could do. Not a lot of advantage, quite a lot of work
*/

/**
 * Standard code module initialisation function.
 */
function init__js_validator()
{
	if (function_exists('require_code')) require_code('js_lex');
	if (function_exists('require_code')) require_code('js_parse');

	global $JS_PARSING_CONDITIONAL;
	$JS_PARSING_CONDITIONAL=false;

	// These are default prototypes. More may be added dynamically, but we can't check against those for consistency.
	global $JS_PROTOTYPES; // Note that '!' prefixed methods are static (only belong to the prototype). All objects also have a constructor, which is what is called when they are called as a function.
	$JS_PROTOTYPES=array( // Each entry is a pair: inherit-from, properties array. Each property is a list: type, name, [function-return-type]. Types prefixed with ! are "this type or anything inherited from it"
		/* ECMA */
		'Object'=>array(NULL,array(array('!','prototype'),/*array('String','Value'),array('function','Get','!Object'),array('function','Put'),array('function','CanPut','Boolean'),array('function','HasProperty','Boolean'),array('function','Delete'),array('String','DefaultValue'),array('function','Construct','!Object'),array('function','Match','Boolean'),*/array('function','!constructor','!Object'),array('function','!toString','String'),array('function','!toLocaleString','String'),array('function','!valueOf','!Object'),array('function','!hasOwnProperty','Boolean'),array('function','!isPrototypeOf','Boolean'),array('function','!propertyIsEnumerable','Boolean'),)),
		'function'=>array('Object',array(array('arguments','arguments'),array('function','Call','!Object'),array('function','HasInstance','Boolean'),)), // Has special language support
		'Function'=>array('function',array()),
		'Array'=>array('Object',array(array('Number','length'),array('function','concat','Array'),array('function','join','String'),array('function','pop','!Object'),array('function','push','Number'),array('function','reverse'),array('function','shift','!Object'),array('function','slice','Array'),array('function','sort'),array('function','splice','Array'),array('function','unshift','Number'),)), // Has special language support
		'String'=>array('Object',array(array('Number','length'),array('function','!fromCharCode','String'),array('function','charAt','String'),array('function','charCodeAt','String'),array('function','concat','String'),array('function','indexOf','Number'),array('function','lastIndexOf','Number'),array('function','localeCompare','Boolean'),array('function','match','Boolean'),array('function','replace','String'),array('function','search','Boolean'),array('function','slice','String'),array('function','split','StringArray'),array('function','substr','String'),array('function','substring','String'),array('function','toLowerCase','String'),array('function','toUpperCase','String'),array('function','toLocaleLowerCase','String'),array('function','toLocaleUpperCase','String'),)), // Has special language support
		'Boolean'=>array('Object',array(array('function','toString','String'),)), // Has special language support
		'Number'=>array('Object',array(array('Number','!MAX_VALUE'),array('Number','!MIN_VALUE'),array('Number','!NaN'),array('Number','!NEGATIVE_INFINITY'),array('Number','!POSITIVE_INFINITY'),array('function','toString','String'),array('function','toLocaleString','String'),array('function','toFixed','String'),array('function','toExponential','String'),array('function','toPrecision','String'),)), // Has special language support
		'Math'=>array('Object',array(array('Number','!E'),array('Number','!LN10'),array('Number','!LN2'),array('Number','!LOG2E'),array('Number','!LOG10E'),array('Number','!PI'),array('Number','!SQRT1_2'),array('Number','!SQRT2'),array('function','!abs','Number'),array('function','!acos','Number'),array('function','!asin','Number'),array('function','!atan','Number'),array('function','!atan2','Number'),array('function','!ceil','Number'),array('function','!cos','Number'),array('function','!exp','Number'),array('function','!floor','Number'),array('function','!log','Number'),array('function','!max','Number'),array('function','!min','Number'),array('function','!pox','Number'),array('function','!random','Number'),array('function','!round','Number'),array('function','!sin','Number'),array('function','!sqrt','Number'),array('function','!tan','Number'),)),
		'Date'=>array('Object',array(array('function','!parse','Number'),array('function','!UTC','Number'),array('function','toDateString','String'),array('function','toTimeString','String'),array('function','toLocaleString','String'),array('function','toLocaleDateString','String'),array('function','toLocaleTimeString','String'),array('function','getTime','Number'),array('function','getFullYear','Number'),array('function','getUTCFullYear','Number'),array('function','getMonth','Number'),array('function','getUTCMonth','Number'),array('function','getDate','Number'),array('function','getUTCDate','Number'),array('function','getDay','Number'),array('function','getUTCDay','Number'),array('function','getHours','Number'),array('function','getUTCHours','Number'),array('function','getMinutes','Number'),array('function','getUTCMinutes','Number'),array('function','getSeconds','Number'),array('function','getUTCSeconds','Number'),array('function','getMilliseconds','Number'),array('function','getUTCMilliseconds','Number'),array('function','getTimezoneOffset','Number'),array('function','setTime'),array('function','setMilliseconds'),array('function','setUTCMilliseconds'),array('function','setSeconds'),array('function','setUTCSeconds'),array('function','setMinutes'),array('function','setUTCMinutes'),array('function','setHours'),array('function','setUTCHours'),array('function','setDate'),array('function','setUTCDate'),array('function','setMonth'),array('function','setUTCMonth'),array('function','setFullYear'),array('function','setUTCFullYear'),array('function','toUTCString','String'),)),
		'RegExp'=>array('Object',array(array('String','$1'),/*array('function','compile'),*/array('String','$2'),array('String','$3'),array('String','$4'),array('String','$5'),array('function','exec','Boolean'),array('function','test','Boolean'),array('String','source'),array('Boolean','global'),array('Boolean','ignoreCase'),array('Boolean','multiline'),array('Number','lastIndex'),)), // Has special language support
		'Error'=>array('Object',array(array('String','!name'),array('String','!message'),)),
		'EvalError'=>array('Error',array()),
		'RangeError'=>array('Error',array()),
		'ReferenceError'=>array('Error',array()),
		'SyntaxError'=>array('Error',array()),
		'TypeError'=>array('Error',array()),
		'URIError'=>array('Error',array()),
		'Null'=>array('Object',array()),
		'Undefined'=>array('Object',array()),
		// ArgumentError, AttributeError, ConstantError, DefinitionError, UninitializedError: exist in Mozilla javascript

		/* Browser-Library / DOM */
		'Node'=>array('Object',array(array('String','nodeName'),array('Number','nodeType'),array('String','nodeValue'),array('NodeArray','childNodes'),array('!Node','firstChild'),array('!Node','lastChild'),array('!Node','nextSibling'),array('!Node','parentNode'),array('!Node','previousSibling'),array('!XMLDocument','ownerDocument'),array('function','appendChild','!Node'),array('function','cloneNode','!Node'),array('function','insertBefore','!Node'),array('function','removeChild','!Node'),array('function','replaceChild','!Node'),array('function','hasChildNodes','Boolean'),)), // nodeType: 1=Element,2=Attribute,3=Text,9=Document
		'XMLDocument'=>array('Node',array(array('Implementation','implementation'),array('function','createElement','!Element'),array('function','createTextNode','TextNode'),array('function','getElementById','!Element'),array('function','createAttribute','Attribute'),array('HTML','documentElement'),array('Boolean','async'),array('function','load'),array('function','loadXML'),array('function','importNode','!Node'),array('function','cloneNode','!Node'),)),
		'Document'=>array('XMLDocument',array(array('String','referrer'),array('String','title'),array('String','URL'),array('String','cookie'),array('Body','body'),array('function','getElementsByName','ElementArray'),array('function','getElementsByTagName','ElementArray'),/*array('ElementArray','images'),array('ElementArray','links'),array('FormArray','forms'),*/array('Selection','selection'),array('function','createRange','Range'),array('function','open'),array('function','write'),array('function','close'),array('Boolean','designMode'),array('function','execCommand','Boolean'),array('function','queryCommandEnabled','Boolean'),array('function','queryCommandState','Boolean'),array('function','queryCommandValue','!Object'),)),
		'Element'=>array('Node',array(array('function','addEventListener','Boolean'),array('function','attachEvent','Boolean'),array('function','getElementsByTagName','ElementArray'),array('function','setAttribute'),array('function','getAttribute'),array('function','Attribute'),array('Array','attributes' /* this is only good for Raw-XML, not XHTML-in-IE */),array('function','onresize'),array('function','onmouseup'),array('function','onmouseover'),array('function','onmouseout'),array('function','onmousemove'),array('function','onmousedown'),array('function','onkeyup'),array('function','onkeypress'),array('function','onkeydown'),array('function','ondblclick'),array('function','onclick'),array('Number','selectionStart'),array('Number','selectionEnd'),array('String','className'),array('String','id'),/*array('String','innerHTML'),*/array('String','innerText'),array('!Object','style'),array('Number','selectionStart'),array('Number','selectionEnd'),array('Number','offsetWidth'),array('Number','offsetHeight'),array('Number','offsetLeft'),array('Number','offsetTop'),array('!Element','offsetParent'),)),
		'TextNode'=>array('Node',array(array('function','appendData'),array('function','deleteData'),array('function','insertData'),array('function','replaceData'),array('function','substringData','String'),array('function','splitText','TextNode'),array('String','data'),)),
		'Attribute'=>array('Object',array(array('String','name'),array('String','value'),)),
		'Body'=>array('Element',array(/*array('function','onmousewheel'),*/array('function','onpagehide'),array('function','onpageshow'),array('function','onunload'),array('function','onload'),array('Number','offsetWidth'),array('Number','offsetHeight'),array('Number','scrollWidth'),array('Number','scrollHeight'),array('Number','scrollTop'),array('Number','scrollLeft'),)),
		'Caption'=>array('Element',array()),
		'HTML'=>array('Element',array(array('Number','clientWidth'),array('Number','clientHeight'),array('Number','scrollWidth'),array('Number','scrollHeight'),array('Number','scrollTop'),array('Number','scrollLeft'),)),
		'Form'=>array('Element',array(array('Array','elements'),array('function','submit'),array('function','reset'),)),
		'Img'=>array('Element',array(array('Boolean','complete'),)),
		'FormField'=>array('Element',array(array('Form','form'),array('function','focus'),array('function','onfocus'),array('function','onchange'),array('function','onblur'),array('Boolean','disabled'),array('String','name'),)),
		'Select'=>array('FormField',array(array('function','onselect'),array('function','select'),array('Number','selectedIndex'),array('Boolean','multiple'),array('Array','options'),)),
		'Option'=>array('FormField',array(array('String','value'),array('Boolean','defaultSelected'),array('Boolean','selected'),)),
		'Checkbox'=>array('FormField',array(array('String','value'),array('Boolean','defaultChecked'),array('Boolean','checked'),)),
		'InputRadio'=>array('FormField',array(array('String','value'),array('Boolean','defaultChecked'),array('Boolean','checked'),)),
		'InputText'=>array('FormField',array(array('String','value'),array('Boolean','readOnly'),)),
		'TextArea'=>array('FormField',array(array('String','value'),array('Number','scrollWidth'),array('Number','scrollHeight'),array('Number','scrollTop'),array('Number','scrollLeft'),)),
		'Frame'=>array('Element',array(array('HTML','contentDocument'),array('Window','contentWindow'),)),
		'Table'=>array('Element',array(array('TableRowArray','rows'),array('TableRowArray','tBodies'),array('TableFooter','tFoot'),array('TableHeader','tHead'),array('function','createCaption','Caption'),array('function','createTFoot','TableFooter'),array('function','createTHead','TableHeader'),array('function','insertRow','TableRow'),array('function','deleteCaption'),array('function','deleteRow'),array('function','deleteTFoot'),array('function','deleteTHead'),)),
		'TableRow'=>array('Element',array(array('ElementArray','cells'),array('Number','sectionRowIndex'),array('Number','rowIndex'),array('function','insertCell','TableData'),array('function','deleteCell'),)),
		'Self'=>array('Window',array(array('Number','outerHeight'),array('Number','outerWidth'),array('Number','screenTop'),array('Number','screenLeft'),array('Number','screenX'),array('Number','screenY'),)),
		'Screen'=>array('Object',array(array('Number','availWidth'),array('Number','availHeight'),array('Number','colorDepth'),array('Number','width'),array('Number','height'),)),
		'Window'=>array('Object',array(/*array('function','onmousewheel'),*/array('function','onpagehide'),array('function','onpageshow'),array('function','onunload'),array('function','onload'),array('History','history'),array('Event','event'),array('String','status'),array('Document','document'),array('FrameArray','frames'),array('Navigator','navigator'),array('Location','location'),array('Screen','screen'),array('Boolean','closed'),array('Window','opener'),array('Window','parent'),array('Self','self'),array('Window','top'),array('function','alert'),array('function','blur'),array('function','focus'),array('function','clearInterval'),array('function','clearTimeout'),array('function','close'),array('function','confirm','Boolean'),array('function','moveBy'),array('function','open','Window'),array('function','print'),array('function','prompt','!Object'),array('function','scrollBy'),array('function','scrollTo'),array('function','setInterval','Number'),array('function','setTimeout','Number'),array('function','encodeURIComponent','String'),array('function','encodeURI','String'),array('function','decodeURIComponent','String'),array('function','decodeURI','String'),array('function','isFinite','Boolean'),array('function','isNaN','Boolean'),array('function','parseFloat','Number'),array('function','parseInt','Number'),array('function','eval','!Object'),array('function','void','undefined')/*,array('function','getAttention')*/,)),
		'Event'=>array('Object',array(array('function','stopPropagation'),array('Boolean','cancelBubble'),array('!Element','target'),array('!Element','srcElement'),array('!Element','fromElement'),array('!Element','relatedTarget'),array('Number','clientX'),array('Number','clientY'),array('Number','offsetX'),array('Number','offsetY'),array('Number','pageX'),array('Number','pageY'),array('Number','screenX'),array('Number','screenY'),array('Boolean','altKey'),array('Boolean','metaKey'),array('Boolean','ctrlKey'),array('Boolean','shiftKey'),array('Number','keyCode'),array('Number','which'),array('String','charCode'),array('Number','button'),array('String','type'),)),
		'History'=>array('Object',array(array('Number','length'),array('function','back'),array('function','forward'),array('function','go'),)),
		'Location'=>array('Object',array(array('String','hash'),array('String','host'),array('String','hostname'),array('String','href'),array('String','pathname'),array('Number','port'),array('String','protocol'),array('String','search'),array('function','assign'),array('function','reload'),array('function','replace'),)),
		'Navigator'=>array('Object',array(array('String','appCodeName'),array('String','appName'),array('Number','appVersion'),array('Boolean','cookieEnabled'),array('String','platform'),array('String','userAgent'),array('function','javaEnabled','Boolean'),array('StringArray','plugins'),)),
		'XMLHttpRequest'=>array('Object',array(array('function','abort'),array('function','getAllResponseHeaders','String'),array('function','getResponseHeader','String'),array('function','open'),array('function','send'),array('function','setRequestHeader'),array('Function','onreadystatechange'),array('Number','readyState'),array('String','responseText'),array('XMLDocument','responseXML'),array('Number','status'),array('String','statusText'),)),
		'ActiveXObject'=>array('Object',array()),
		'Selection'=>array('Object',array(array('function','createRange','TextRange'),)), // IE style (document.selection)
		'TextRange'=>array('Object',array(array('String','text'),array('function','collapse'),array('function','findText','Boolean'),array('function','move','Number'),array('function','moveEnd','Number'),array('function','moveStart','Number'),array('function','select'),array('function','moveToElementText'),)), // IE style
		'Range'=>array('Object',array(array('Number','endOffset'),array('Number','startOffset'),array('function','setStart'),array('function','setEnd'),array('function','collapse'),array('Boolean','collapsed'),)), // Mozilla Style
		'Implementation'=>array('Object',array(array('function','createDocument','XMLDocument'),)),
		'EmbedLiveAudioOrActiveMovie'=>array('Object',array(array('function','play'),array('function','pause'),array('function','stop'),)),
		'EmbedActiveXFlash'=>array('Object',array(array('Number','ReadyState'),array('Number','TotalFrames'),array('Number','FrameNum'),array('Boolean','Playing'),array('String','Quality'),array('Number','ScaleMode'),array('Number','AlignMode'),array('String','Backgroundcolor'),array('Boolean','Loop'),array('String','Movie'),array('function','Play'),array('function','Stop'),array('function','Back'),array('function','Forward'),array('function','Rewind'),)),
		'EmbedLiveConnectFlash'=>array('Object',array(array('function','LoadMovie'),array('function','GetVariable','!Object'),array('function','Play'),array('function','StopPlay'),array('function','IsPlaying','Boolean'),array('function','GotoFrame'),array('function','TotalFrames','Number'),array('function','Rewind'),array('function','SetZoomRect'),array('function','Zoom'),array('function','Pan'),array('function','PercentLoaded','Number'),)),
		'DomException'=>array('Error',array(array('Number','code'),array('Number','!INDEX_SIZE_ERR'),array('Number','!DOMSTRING_SIZE_ERR'),array('Number','!HIERARCHY_REQUEST_ERR'),array('Number','!WRONG_DOCUMENT_ERR'),array('Number','!INVALID_CHARACTER_ERR'),array('Number','!NO_DATA_ALLOWED_ERR'),array('Number','!NO_MODIFICATION_ALLOWED_ERR'),array('Number','!NOT_FOUND_ERR'),array('Number','!NOT_SUPPORTED_ERR'),array('Number','!INUSE_ATTRIBUTE_ERR'),array('Number','!INVALID_STATE_ERR'),array('Number','!SYNTAX_ERR'),array('Number','!INVALID_MODIFICATION_ERR'),array('Number','!NAMESPACE_ERR'),array('Number','!INVALID_ACCESS_ERR'),array('Number','!VALIDATION_ERR'),array('Number','!TYPE_MISMATCH_ERR'),)),

		'StringArray'=>array('Array',array()),
		'NodeArray'=>array('Array',array()),
		'ElementArray'=>array('Array',array()),
		'FormArray'=>array('Array',array()),
		'TableRowArray'=>array('Array',array()),
		'FrameArray'=>array('Array',array()),
	);

	// Hard-code all the inheritance
	$found_one=false;
	do
	{
		$found_one=false;
		foreach ($JS_PROTOTYPES as $prototype=>$details)
		{
			if ((!is_null($details[0])) && ($details[0]!=''))
			{
				if (!array_key_exists(2,$JS_PROTOTYPES[$details[0]])) $JS_PROTOTYPES[$details[0]][2]=array(); // This is an inverse-list of all the classes inheriting from self

				$t=$details[0];
				do
				{
					$JS_PROTOTYPES[$t][2]+=array($prototype=>1);
					$t=isset($JS_PROTOTYPES[$t][3])?$JS_PROTOTYPES[$t][3]:$JS_PROTOTYPES[$t][0];
				}
				while (!is_null($t));
				$details[1]=array_merge($JS_PROTOTYPES[$details[0]][1],$details[1]);
				if (!isset($details[2])) $details[3]=$details[0];
				$details[0]=$JS_PROTOTYPES[$details[0]][0];
				$found_one=true;

				$JS_PROTOTYPES[$prototype]=$details;
			}
		}
	}
	while ($found_one);
	reset_js_global_variables();
}

/**
 * Return the global variable array to the defaults.
 */
function reset_js_global_variables()
{
	// These are the global Javascript objects (static prototypes like 'Math') are also used but those are difference because they can be instantiated, and because some, like Array, can act like functions). In actual fact, everything is "window.", but we don't want to force qualifying into window because it would block our detection
	global $JS_GLOBAL_VARIABLES;
	$JS_GLOBAL_VARIABLES=array(
		'window'=>array('function_return'=>NULL,'unused_value'=>NULL,'first_mention'=>0,'is_global'=>true,'types'=>array('Window')),
	);
	global $JS_PROTOTYPES;
	foreach (array_keys($JS_PROTOTYPES) as $name)
	{
		$JS_GLOBAL_VARIABLES[$name]=array('function_return'=>NULL,'unused_value'=>NULL,'first_mention'=>0,'is_global'=>true,'types'=>array($name));
	}
	foreach ($JS_PROTOTYPES['Window'][1] as $t)
	{
		$JS_GLOBAL_VARIABLES[$t[1]]=array('function_return'=>isset($t[2])?$t[2]:NULL,'unused_value'=>NULL,'first_mention'=>0,'is_global'=>true,'types'=>array($t[0]));
	}
}

/**
 * Check some JS code for validity.
 *
 * @param  string			Code
 * @param  boolean			Whether to return raw-errors
 * @return array			Standard validator report output
 */
function check_js($data,$raw_errors=false)
{
	if ((strlen($data)>15000) && (preg_match('#^localhost[\.\:$]#',ocp_srv('HTTP_HOST'))!=0) && (function_exists('get_param_integer')) && (get_param_integer('keep_huge',0)==0))
	{
		$out=array();
		$out['line']=0;
		$out['pos']=0;
		$out['global_pos']=0;
		$out['error']='JS: There is a 15KB JS validation limit for this validator';
		if ($raw_errors) return array(array(0=>$out['error'],'raw'=>true));
		return array('level_ranges'=>NULL,'tag_ranges'=>NULL,'value_ranges'=>NULL,'errors'=>array($out));
	}

	global $JS_ERRORS,$JS_TAG_RANGES,$JS_VALUE_RANGES;
	$JS_ERRORS=array();
	$JS_TAG_RANGES=array();
	$JS_VALUE_RANGES=array();
	$lexed=js_lex($data);
	if (!is_null($lexed))
	{
		$parsed=js_parse();
		if (!is_null($parsed))
		{
			_check_js($parsed);
		}
	}

	unset($GLOBALS['JS_LEX_TOKENS']);

	$errors=array();
	if ($raw_errors)
	{
		foreach ($JS_ERRORS as $error)
		{
			$out=array(0=>$error[0],'raw'=>true,'pos'=>$error[3]);
			$errors[]=$out;
		}
		unset($GLOBALS['JS_ERRORS']);
		return $errors;
	}
	foreach ($JS_ERRORS as $error)
	{
		$out=array();
		$out['line']=$error[2];
		$out['pos']=$error[1];
		$out['global_pos']=$error[3];
		$out['error']=$error[0];
		$errors[]=$out;
	}
	unset($GLOBALS['JS_ERRORS']);
	return array('level_ranges'=>NULL,'tag_ranges'=>$JS_TAG_RANGES,'value_ranges'=>$JS_VALUE_RANGES,'errors'=>$errors);
}

/**
 * Do the actual code check on the parse structure.
 *
 * @param  map				Parse structure
 */
function _check_js($structure)
{
	global $JS_GLOBAL_VARIABLES,$JS_LOCAL_VARIABLES;

	$JS_LOCAL_VARIABLES=$JS_GLOBAL_VARIABLES;

	foreach ($structure['functions'] as $function)
	{
		$JS_GLOBAL_VARIABLES[$function['name']]=array('function_return'=>'!Object','is_global'=>true,'types'=>array('function'),'unused_value'=>NULL,'first_mention'=>$function['offset']);
	}
	js_check_command($structure['main'],0);
	// Update global variables
	foreach ($JS_LOCAL_VARIABLES as $name=>$v)
	{
		if (isset($JS_GLOBAL_VARIABLES[$name]))
		{
			$JS_GLOBAL_VARIABLES[$name]['types']=array_unique(array_merge($JS_GLOBAL_VARIABLES[$name]['types'],$v['types']));
		} else
		{
			$JS_GLOBAL_VARIABLES[$name]=$v;
			$JS_GLOBAL_VARIABLES[$name]['is_global']=true;
		}
	}
	foreach ($structure['functions'] as $function)
	{
		$JS_LOCAL_VARIABLES=$JS_GLOBAL_VARIABLES;
		js_check_function($function);
	}

	// Check for type conflicts in the global variables
	js_check_variable_list($JS_GLOBAL_VARIABLES);
}

/**
 * Check a function declaration.
 *
 * @param  map				The function details
 */
function js_check_function($function)
{
	global $JS_LOCAL_VARIABLES,$JS_GLOBAL_VARIABLES;
	$old_local=$JS_LOCAL_VARIABLES;

	// Initialise any local variables that come from parameters
	foreach ($function['parameters'] as $p)
	{
		js_add_variable_reference($p[1],$function['offset'],true);
	}
	js_add_variable_reference('arguments',$function['offset'],true);
	js_set_ocportal_type('arguments','Array');
	js_add_variable_reference('this',$function['offset'],true);
	js_add_variable_reference('event',$function['offset'],true);

	// Check commands
	js_check_command($function['code'],0);

	// Check for type conflicts in the variables
	foreach ($JS_LOCAL_VARIABLES as $variable=>$vinfo)
	{
		if (isset($old_local[$variable]))
		{
			$old_local[$variable]['unused_value']=$vinfo['unused_value'];
			unset($JS_LOCAL_VARIABLES[$variable]);
		}
	}
	js_check_variable_list($JS_LOCAL_VARIABLES);

	$JS_LOCAL_VARIABLES=$old_local;
}

/**
 * Check a variable list for consistency.
 *
 * @param  list				The variable list
 */
function js_check_variable_list($JS_LOCAL_VARIABLES)
{
	global $JS_PROTOTYPES;
	foreach ($JS_LOCAL_VARIABLES as $name=>$v)
	{
		// Check for type conflicts
		$conflict=false;
		$unique=array_unique($v['types']);
		foreach ($unique as $t1)
		{
			foreach ($unique as $t2)
			{
				if ($t1=='') continue; // Weird
				if ($t2=='') continue; // Weird
				if ($t1=='Null') continue;
				if ($t2=='Null') continue;
				if ($t1==$t2) continue;

				if (($t1[0]!='!') && ($t2[0]!='!'))
				{
					$conflict=true;
					break;
				}

				$_t1=($t1[0]=='!')?substr($t1,1):$t1;
				$_t2=($t2[0]=='!')?substr($t2,1):$t2;
				$potentials1=array($_t1=>1);
				$potentials2=array($_t2=>1);
				if (($t1[0]=='!') && (isset($JS_PROTOTYPES[substr($t1,1)][2])))
				{
					$potentials1+=$JS_PROTOTYPES[substr($t1,1)][2];
				}
				if (($t2[0]=='!') && (isset($JS_PROTOTYPES[substr($t2,1)][2])))
				{
					$potentials2+=$JS_PROTOTYPES[substr($t2,1)][2];
				}
				if (count(array_intersect(array_keys($potentials1),array_keys($potentials2)))==0)
				{
					$conflict=true;
				}
			}
		}
		if ($conflict)
		{
			$a=implode(',',array_unique($v['types']));
			$both=array_unique($v['types']);
			sort($both);
			if (($both!=array('ActiveXObject','XMLHttpRequest')) && ($both!=array('ActiveXObject','XMLDocument')))
			{
				js_log_warning('CHECKER','Type conflict for variable: '.$name.' ('.$a.')',$v['first_mention']);
			}
		}

		// Check for non-used variables
		if (($v['unused_value']) && ($name!='__return') && ($name!='_') && (!$v['is_global']) && (!in_array($name,array('this','arguments','event'))))
		{
			js_log_warning('CHECKER','Non-used '.($v['unused_value']?'value':'variable').' (\''.$name.'\')',$v['first_mention']);
		}
	}
}

/**
 * Check a parsed command.
 *
 * @param  list				The command
 * @param  integer			The block depth we are searching at
 */
function js_check_command($command,$depth)
{
	global $JS_LOCAL_VARIABLES,$CURRENT_CLASS,$FUNCTION_SIGNATURES;
	foreach ($command as $i=>$c)
	{
		if ($c==array()) continue;

		if (is_integer($c[count($c)-1]))
		{
			$c_pos=$c[count($c)-1];
			$or=false;
		} else
		{
			$c_pos=$c[count($c)-2];
			$or=true;
		}

		switch ($c[0])
		{
			case 'INNER_FUNCTION':
				js_check_function($c[1]);
				break;
			case 'RETURN':
				$ret_type=js_check_expression($c[1]);
				js_add_variable_reference('__return',$c_pos,false,true);
				js_set_ocportal_type('__return',$ret_type);
				if (!isset($JS_LOCAL_VARIABLES['__return']['mentions']))
				{
					$JS_LOCAL_VARIABLES['__return']['mentions']=array();
				}
				$JS_LOCAL_VARIABLES['__return']['mentions'][]=$c_pos;
				if (count($command)-1>$i) js_log_warning('CHECKER','There is unreachable code',$c_pos);
				break;
			case 'SWITCH':
				$switch_type=js_check_expression($c[1]);
				foreach ($c[2] as $case)
				{
					/*if (!is_null($case[0]))
					{
						$passes=js_ensure_type(array($switch_type),js_check_expression($case[0]),$c_pos,'Switch type inconsistency');
						if ($passes) js_infer_expression_type_to_variable_type($switch_type,$case[0]);
					}*/
					js_check_command($case[1],$depth+1);
				}
				break;
			case 'WITH':
				js_log_warning('CHECKER','\'with\' is deprecated and slow - and the checker will not take it into account when checking vars',$c_pos);
				js_check_variable($c[1]);
				js_check_command($c[2],$depth);
				break;
			case 'IF':
				$rem=$GLOBALS['JS_PARSING_CONDITIONAL'];
				$GLOBALS['JS_PARSING_CONDITIONAL']=true;
				$t=js_check_expression($c[1]);
				$GLOBALS['JS_PARSING_CONDITIONAL']=$rem;
				//$passes=js_ensure_type(array('Boolean'),$t,$c_pos,'Conditionals must be Boolean (if) [is '.$t.']');
				//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$c[1]);
				js_check_command($c[2],$depth);
				break;
			case 'IF_ELSE':
				$rem=$GLOBALS['JS_PARSING_CONDITIONAL'];
				$GLOBALS['JS_PARSING_CONDITIONAL']=true;
				$t=js_check_expression($c[1]);
				$GLOBALS['JS_PARSING_CONDITIONAL']=$rem;
				//$passes=js_ensure_type(array('Boolean'),$t,$c_pos,'Conditionals must be Boolean (if-else)');
				//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$c[1]);
				js_check_command($c[2],$depth);
				js_check_command($c[3],$depth);
				break;
			case 'FOREACH_list':
				//$passes=js_ensure_type(array('Array'),js_check_expression($c[1]),$c_pos,'FOR-OF must take Array');
				//if ($passes) js_infer_expression_type_to_variable_type('Array',$c[1]);
				js_add_variable_reference($c[2][1],$c_pos,true);
				js_check_command($c[3],$depth+1);
				break;
			case 'FOR':
				if (!is_null($c[1])) js_check_command(array($c[1]),$depth+1);
				if (!is_null($c[3])) js_check_command(array($c[3]),$depth+1);
				$passes=js_ensure_type(array('Boolean'),js_check_expression($c[2]),$c_pos,'Loop conditionals must be Boolean (for)');
				//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$c[2]);
				if (!is_null($c[4])) js_check_command($c[4],$depth+1);
				break;
			case 'DO':
				js_check_command($c[2],$depth+1);
				$passes=js_ensure_type(array('Boolean'),js_check_expression($c[1]),$c_pos,'Loop conditionals must be Boolean (do)');
				//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$c[1]);
				break;
			case 'WHILE':
				$passes=js_ensure_type(array('Boolean'),js_check_expression($c[1]),$c_pos,'Loop conditionals must be Boolean (while)');
				//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$c[1]);
				js_check_command($c[2],$depth+1);
				$rem=$GLOBALS['JS_PARSING_CONDITIONAL'];
				$GLOBALS['JS_PARSING_CONDITIONAL']=true;
				js_check_expression($c[1]); // To fixup "unused variable" issues (might double report, but that's better that false-positives)
				$GLOBALS['JS_PARSING_CONDITIONAL']=$rem;
				break;
			case 'TRY':
				js_check_command($c[1],$depth+1);
				break;
			case 'CATCH':
				js_check_command($c[2],$depth+1);
				break;
			case 'FINALLY':
				js_check_command($c[1],$depth+1);
				break;
			case 'THROW':
				js_check_expression($c[1]);
				break;
			case 'DELETE':
				js_check_variable($c[1]);
				break;
			case 'CONTINUE':
				break;
			case 'BREAK':
				break;
			case 'VAR':
				foreach ($c[1] as $var)
				{
					js_add_variable_reference($var[1],$c_pos,true);
					if (!is_null($var[2]))
					{
						js_set_ocportal_type($var[1],js_check_expression($var[2]));
					}
				}
				break;

			default:
				js_check_expression($c,true);
		}

		if ($or) js_check_command(array($c[count($c)-1]),$depth);
	}
}

/**
 * Check an assignment statement.
 *
 * @param  list				The complex assignment details
 * @param  integer			The position this is at in the parse
 * @return string				The assigned type
 */
function js_check_assignment($c,$c_pos)
{
	$e_type=js_check_expression($c[3]);
	$op=$c[1];
	$target=$c[2];

	// Special assignment operational checks
	if (in_array($op,array('PLUS_EQUAL')))
	{
		js_ensure_type(array('Array','Number','String'),$e_type,$c_pos,'Can only perform addition to strings or arrays or numbers (not '.$e_type.')');
		if ($target[0]=='VARIABLE')
		{
			$v_type=js_get_variable_type($target);
			js_ensure_type(array('Array','Number','String'),$v_type,$c_pos,'Can only perform addition to strings or arrays or numbers (not '.$v_type.')');
		}
	}
	if (in_array($op,array('DIV_EQUAL','MUL_EQUAL','MINUS_EQUAL','SL_EQUAL','SR_EQUAL','ZSR_EQUAL','BW_AND_EQUAL','BW_OR_EQUAL')))
	{
		js_ensure_type(array('Number'),$e_type,$c_pos,'Can only perform relative arithmetic with numbers (not '.$e_type.')');
		if ($target[0]=='VARIABLE')
		{
			$v_type=js_get_variable_type($target);
			js_ensure_type(array('Number'),$v_type,$c_pos,'Can only perform relative arithmetic with numbers (not '.$v_type.')');
		}
	}

	// js_check_variable will do the internalised checks. Type conflict checks will be done at the end of the function, based on all the types the variable has been set with. Variable type usage checks are done inside expressions.
	if ($target[0]=='VARIABLE')
	{
		if (($op=='EQUAL') && (count($target[2])==0))
		{
			if ($target[1]=='this') js_log_warning('CHECKER','\'this\' is immutable',$c_pos);

			js_add_variable_reference($target[1],$c_pos,false,$e_type=='Null');
			js_set_ocportal_type($target[1],$e_type);
		}
		$type=js_check_variable($target);
		return $type;
	}

	// Should never get here
	return '!Object';
}

/**
 * Check an expression.
 *
 * @param  list				The complex expression
 * @param  boolean			Whether the expression is being used as a command (i.e. whether the expression is not used for the result, but rather, the secondary consequences of calculating it)
 * @return string				The type
 */
function js_check_expression($e,$secondary=false)
{
	$c_pos=$e[count($e)-1];

	if ($e[0]=='VARIABLE_REFERENCE') $e[0]='VARIABLE'; // Handled in the same way
	if ($e[0]=='SOLO')
	{
		$type=js_check_expression($e[1]);
		return $type;
	}
	if ($e[0]=='UNARY_IF')
	{
		$rem=$GLOBALS['JS_PARSING_CONDITIONAL'];
		$GLOBALS['JS_PARSING_CONDITIONAL']=true;
		$t=js_check_expression($e[1]);
		$GLOBALS['JS_PARSING_CONDITIONAL']=$rem;
		//$passes=js_ensure_type(array('Boolean'),$t,$c_pos,'Conditionals must be Boolean (unary)');
		//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$e[1]);
		$type_a=js_check_expression($e[2][0]);
		$type_b=js_check_expression($e[2][1]);
		/*if (($type_a!='Null') && ($type_b!='Null'))
		{
			$passes=js_ensure_type(array($type_a),$type_b,$c_pos,'Type symettry error in unary operator');
			//if ($passes) js_infer_expression_type_to_variable_type($type_a,$e[2][1]);
		}*/
		return $type_a;
	}
	if (in_array($e[0],array('BOOLEAN_AND','BOOLEAN_OR')))
	{
		$exp=js_check_expression($e[1]);
		//$passes=js_ensure_type(array('Boolean'),$exp,$c_pos-1,'Can only use Boolean combinators with Booleans');
		//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$e[1]);
		$exp=js_check_expression($e[2]);
		//$passes=js_ensure_type(array('Boolean'),$exp,$c_pos,'Can only use Boolean combinators with Booleans');
		//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$e[2]);
		return 'Boolean';
	}
	if (in_array($e[0],array('BW_XOR','BW_AND','BW_OR','SL','SR','ZSR','REMAINDER')))
	{
		$passes=js_ensure_type(array('Number'),js_check_expression($e[1]),$c_pos-1,'Can only use integer combinators with Numbers');
		//if ($passes) js_infer_expression_type_to_variable_type('Number',$e[1]);
		$passes=js_ensure_type(array('Number'),js_check_expression($e[2]),$c_pos,'Can only use integer combinators with Numbers');
		//if ($passes) js_infer_expression_type_to_variable_type('Number',$e[2]);
		return 'Number';
	}
	if (in_array($e[0],array('CONC')))
	{
		$type_a=js_check_expression($e[1]);
		$type_b=js_check_expression($e[2]);
		$passes=js_ensure_type(array('String'),$type_a,$c_pos-1,'Can only use string combinators with Strings (1) (not '.$type_a.')');
		//if ($passes) js_infer_expression_type_to_variable_type('String',$e[1]);
		$passes=js_ensure_type(array('String'),$type_b,$c_pos,'Can only use string combinators with Strings (2) (not '.$type_b.')');
		//if ($passes) js_infer_expression_type_to_variable_type('String',$e[2]);
		return 'String';
	}
	if (in_array($e[0],array('SUBTRACT','MULTIPLY','DIVIDE')))
	{
		$type_a=js_check_expression($e[1]);
		$t=js_check_expression($e[2]);
		js_ensure_type(array('Number','Date'),$type_a,$c_pos-1,'Can only use arithmetical combinators with Numbers (1) (not '.$type_a.')');
		js_ensure_type(array('Number','Date'),$t,$c_pos,'Can only use arithmetical combinators with Numbers (2) (not '.$t.')');
		return ($e[0]=='DIVIDE')?'Number':$type_a;
	}
	if (in_array($e[0],array('ADD')))
	{
		$type_a=js_check_expression($e[1]);
		$t=js_check_expression($e[2]);
		js_ensure_type(array('Number','Array','String','Date'),$type_a,$c_pos-1,'Can only use + combinator with Strings/Numbers/Arrays (1) (not '.$type_a.')');
		js_ensure_type(array('Number','Array','String','Date'),$t,$c_pos,'Can only use + combinator with Strings/Numbers/Arrays (2) (not '.$t.')');
		return $type_a;
	}
	if (in_array($e[0],array('IS_GREATER_OR_EQUAL','IS_SMALLER_OR_EQUAL','IS_GREATER','IS_SMALLER')))
	{
		$type_a=js_check_expression($e[1]);
		$type_b=js_check_expression($e[2]);
		js_ensure_type(array('Number','String','Date'),$type_a,$c_pos-1,'Can only use arithmetical comparators with Numbers or Strings');
		js_ensure_type(array('Number','String','Date'),$type_b,$c_pos,'Can only use arithmetical comparators with Numbers or Strings');
		js_ensure_type(array($type_a),$type_b,$c_pos,'Comparators must have type symettric operands ('.$type_a.' vs '.$type_b.')');
		return 'Boolean';
	}
	if (in_array($e[0],array('IS_EQUAL','IS_IDENTICAL','IS_NOT_IDENTICAL','IS_NOT_EQUAL')))
	{
		$type_a=js_check_expression($e[1]);
		$type_b=js_check_expression($e[2]);
		if (($e[0]=='IS_EQUAL') && ($e[2][0]=='LITERAL') && ($e[2][1][0]=='Boolean')) js_log_warning('CHECKER','It\'s redundant to equate to truths',$c_pos);
		$passes=js_ensure_type(array($type_a),$type_b,$c_pos,'Comparators must have type symettric operands ('.$type_a.' vs '.$type_b.')');
		//if ($passes) js_infer_expression_type_to_variable_type($type_a,$e[2]);
		return 'Boolean';
	}
	if ($e[0]=='INSTANCEOF')
	{
		js_check_variable($e[1]);
		return 'Boolean';
	}
	$inner=$e;
	switch ($inner[0])
	{
		case 'PRE_DEC':
			js_ensure_type(array('Number'),js_check_variable($inner[1]),$c_pos,'Can only decrement numbers');
			break;
		case 'PRE_INC':
			js_ensure_type(array('Number'),js_check_variable($inner[1]),$c_pos,'Can only increment numbers');
			break;
		case 'DEC':
			js_ensure_type(array('Number'),js_check_variable($inner[1]),$c_pos,'Can only decrement numbers');
			break;
		case 'INC':
			js_ensure_type(array('Number'),js_check_variable($inner[1]),$c_pos,'Can only increment numbers');
			break;
		case 'ASSIGNMENT':
			$ret=js_check_assignment($inner,$c_pos);
			return $ret;
		case 'OBJECT_OPERATOR':
			$class=js_check_expression($inner[1]);
			if (is_null($class)) return 'Null';
			if ($inner[2][0]=='CALL')
			{
				$ret=js_check_call($inner[2],$c_pos,$class);
				if (is_null($ret))
				{
					if (!$secondary) js_log_warning('CHECKER','(Function (\''.(is_array($inner[1])?'(complex)':$inner[1]).'\') that returns no value used in an expression',$c_pos);
					return '!Object';
				}
			}
			elseif ($inner[2][0]=='VARIABLE')
			{
				$ret=js_check_variable($inner[2],true,false,$class);
			} else
			{
				global $JS_LOCAL_VARIABLES;
				$tmp=$JS_LOCAL_VARIABLES;
				js_check_expression($inner[2]);
				$JS_LOCAL_VARIABLES=$tmp;
				$ret='!Object';
			}
			return $ret;
		case 'CALL':
			$ret=js_check_call($inner,$c_pos);
			if (is_null($ret))
			{
				if (!$secondary) js_log_warning('CHECKER','(Function (\''.(is_array($inner[1])?'(complex)':$inner[1]).'\') that returns no value used in an expression',$c_pos);
				return '!Object';
			}
			return $ret;
		case 'BRACKETED':
			return js_check_expression($inner[1]);
		case 'BOOLEAN_NOT':
			$expression=js_check_expression($inner[1]);
			//$passes=js_ensure_type(array('Boolean'),$expression,$c_pos,'Can only \'NOT\' a Boolean');
			//if ($passes) js_infer_expression_type_to_variable_type('Boolean',$inner[1]);
			return 'Boolean';
		case 'TYPEOF':
			js_check_expression($inner[1]);
			return 'String';
		case 'BW_NOT':
			$type=js_check_expression($inner[1]);
			js_ensure_type(array('Number'),$type,$c_pos,'Can only negate a Number');
			return $type;
		case 'NEGATE':
			$type=js_check_expression($inner[1]);
			js_ensure_type(array('Number'),$type,$c_pos,'Can only negate a Number');
			return $type;
		case 'LITERAL':
			$type=$inner[1][0];
			return $type;
		case 'NEW_OBJECT_FUNCTION':
			js_check_function($inner[1]);
			return 'function';
		case 'NEW_OBJECT':
			foreach ($inner[2] as $param)
			{
				js_check_expression($param);
			}
			if (count($inner[2])!=0)
			{
				js_check_call(array('CALL',array('VARIABLE',$inner[1],array(),$c_pos),$inner[2]),$c_pos,$inner[1]);
			}
			if ($inner[1]=='Array') return '!Array';
			return $inner[1];
		case 'VARIABLE':
			return js_check_variable($inner,true);
	}
	return '!Object';
}

/**
 * Check a function call.
 *
 * @param  list				The (possibly complex) variable that is the function identifier
 * @param  integer			The position this is at in the parse
 * @param  ?string			The class the given variable is in (NULL: global/as-specified-internally-in-c)
 * @return ?string			The return type (NULL: nothing returned)
 */
function js_check_call($c,$c_pos,$class=NULL)
{
	list($type,$ret)=js_check_variable($c[1],true,true,$class);
	if (($type!='function') && ($type!='!Object') && ($c[1][1]!=$type)) // Latter check for case of calling a prototype as a function (e.g. Array)  [a shorthand for construction]
	{
		js_log_warning('CHECKER','Calling an object that does not seem to be of type \'function\' (\''.$type.'\')',$c_pos);
	}

	foreach ($c[2] as $param)
	{
		js_check_expression($param);
	}
	if ($type!='function') return '!Object';
	return $ret;
}

/**
 * Check a variable.
 *
 * @param  list				The (possibly complex) variable
 * @param  boolean			Whether the variable is being used referentially (i.e. not being set)
 * @param  boolean			Whether to return the type and function-return-type pair, rather than just the type
 * @param  ?string			The class the variable is referencing within (NULL: global)
 * @param  boolean			Whether the given class is being referenced directly in static form
 * @return mixed				The return type and possibly function return type (if requested)
 */
function js_check_variable($variable,$reference=false,$function_duality=false,$class=NULL,$allow_static=false)
{
	global $JS_LOCAL_VARIABLES;

	$identifier=$variable[1];
	if (is_array($identifier)) // Normally just a string, but JS is awkward and allows expression :S
	{
		$exp_type=js_check_expression($identifier);
		$variable[1]=$exp_type;
		return js_check_variable($variable,$reference,$function_duality);
	}

	$_class=NULL;

	if (is_null($class))
	{
		if ($identifier[0]!='!') // Sometimes we use fake static objects (like !Object), and we can't start referencing these as real variables
		{
			// Add to reference count if: this specifically is a reference, or it's complex therefore the base is explicitly a reference, or we are forced to add it because it is yet unseen
			if (($reference) || (count($variable[2])!=0) || (!isset($JS_LOCAL_VARIABLES[$identifier])))
			{
				js_add_variable_reference($identifier,$variable[count($variable)-1],false,($reference) || (count($variable[2])!=0));
			} else
			{
				if ((!isset($JS_LOCAL_VARIABLES[$identifier])) && ($identifier!='this') && ($identifier!='__return'))
				{
					js_log_warning('CHECKER','Variable ('.$identifier.') was used without being declared',$variable[3]);
				}
			}
		}
	} else
	{
		global $JS_PROTOTYPES;
		if (isset($JS_PROTOTYPES[$class]))
		{
			$found=NULL;
			foreach ($JS_PROTOTYPES[$class][1] as $_class=>$_found)
			{
				if (($_found[1]==$identifier) || (($_found[1]=='!'.$identifier) && ($allow_static)))
				{
					$found=$_found;
					break;
				}
			}
			if (/*($class!='self') && ($class!='Window') && */($identifier!=$class) && ($class!='Object')) // We're allowed to freely add to Object because we need to to make our own. It's also not likely people will "mistakingly" handle things they think Object has but it doesn't.
			{
				if (($GLOBALS['JS_PARSING_CONDITIONAL']) && (count($variable[2])==0)) // We're running a conditional on this, meaning the user is likely checking to see if it exists (if it's a boolean that doesn't exist, we're in trouble, but unfortunately it's ambiguous).
				{
					// We add the variable, because it might have been guaranteed. We're screwed if it is not a guaranteeing conditional, but it's impossible to test that ("the halting problem")
					if (($class=='Window') || ($class=='Self'))
					{
						js_add_variable_reference($identifier,$variable[count($variable)-1],true);
					}
					$JS_PROTOTYPES[$class][1][]=array('!Object',$identifier); // Could be any type
				}
			}
			if (is_null($found))
			{
				if (/*($class!='self') && ($class!='Window') && */($identifier!=$class) && ($class!='Object')) // We're allowed to freely add to Object because we need to to make our own. It's also not likely people will "mistakingly" handle things they think Object has but it doesn't.
				{
					if ((!$GLOBALS['JS_PARSING_CONDITIONAL']) || (count($variable[2])!=0)) // We're running a conditional on this, meaning the user is likely checking to see if it exists (if it's a boolean that doesn't exist, we're in trouble, but unfortunately it's ambiguous).
					{
						if ($reference) js_log_warning('CHECKER','\''.$identifier.'\' is an unknown member of the class \''.$class.'\'',$variable[3]);
					}
				}
				if ($function_duality) return array('!Object','!Object');
				return '!Object';
			} else
			{
				$_class=$found[0];
				if (count($variable[2])==0)
				{
					if ($function_duality) return array($found[0],isset($found[2])?$found[2]:NULL);
					return $found[0];
				}
			}
		} else
		{
			if ($function_duality) return array('!Object','!Object');
			return '!Object';
		}
	}

	if (count($variable[2])!=0) // Complex: we must perform checks to make sure the base is of the correct type for the complexity to be valid. We must also note any deep variable references used in array index expressions
	{
		// Further depth to scan extractive expressions for?
		if ((in_array($variable[2][0],array('ARRAY_AT','OBJECT_OPERATOR'))) && (count($variable[2][2])!=0))
		{
			js_scan_extractive_expressions($variable[2][2]);
		}

		//js_add_variable_reference($identifier,$variable[count($variable)-1],false,true);

		if ($variable[2][0]=='ARRAY_AT')
		{
			js_check_expression($variable[2][1]);
			$exp_type=js_check_variable(array('VARIABLE',$identifier,array(),$variable[count($variable)-1]),true,false,$class);

//			$passes=js_ensure_type(array('!Array'),$exp_type,$variable[3],'Variable \''.$identifier.'\' must be an Array due to dereferencing (is '.$exp_type.')');
//			if ($passes) js_infer_expression_type_to_variable_type('!Array',$variable[2][1]);
			$pos=strpos($exp_type,'Array');
			if ($pos!==false)
			{
				$exp_type=substr($exp_type,0,$pos);
				if (($exp_type=='') || ($exp_type=='!')) $exp_type='!Object';
			} else
			{
				$exp_type='!Object';
			}
			if (count($variable[2][2])!=0)
			{
				return js_check_variable(array('VARIABLE',$exp_type,$variable[2][2],$variable[count($variable)-1]),true,$function_duality,$exp_type,true);
			}
			if ($function_duality) return array($exp_type,'!Object');
			return $exp_type;
		}
		if ($variable[2][0]=='OBJECT_OPERATOR')
		{
			if (count($variable[2][1][2])!=0)
			{
				if ($function_duality) return array('!Object','!Object');
				return '!Object';
			}
			if (is_null($_class)) $_class=js_check_variable(array('VARIABLE',$identifier,array(),$variable[count($variable)-1]));
			return js_check_variable(array('VARIABLE',$variable[2][1][1],$variable[2][2],$variable[count($variable)-1]),$reference,$function_duality,$_class,$_class==$identifier);
		}

		exit(':( wrt '.$variable[2][0]);
	}

	$function_return=isset($JS_LOCAL_VARIABLES[$identifier]['function_return'])?$JS_LOCAL_VARIABLES[$identifier]['function_return']:NULL;
	if (is_null($function_return))
	{
		if (isset($JS_PROTOTYPES[$identifier])) return $identifier; else $function_return='!Object';
	}
	if ($function_duality) return array(js_get_variable_type($variable),$function_return);
	return js_get_variable_type($variable);
}

/**
 * Scan through a complex variable, checking any expressions embedded in it.
 *
 * @param  list				The complex variable
 */
function js_scan_extractive_expressions($variable)
{
	if ($variable[0]=='ARRAY_AT')
	{
		js_check_expression($variable[1]);
	}

	if ((($variable[0]=='ARRAY_AT') || ($variable[0]=='OBJECT_OPERATOR')) && (count($variable[2])!=0))
	{
		js_scan_extractive_expressions($variable[2]);
	}
}

/**
 * Get the type of a variable.
 *
 * @param  list				The variable
 * @return string				The type
 */
function js_get_variable_type($variable)
{
	global $JS_LOCAL_VARIABLES;

	$identifier=$variable[1];

	if (count($variable[2])!=0) return '!Object'; // Too complex

	if (!isset($JS_LOCAL_VARIABLES[$identifier])) return '!Object';

	if (count($JS_LOCAL_VARIABLES[$identifier]['types'])==0) return '!Object'; // There is a problem, but it will be identified elsewhere.

	$temp=array_unique(array_values(array_diff($JS_LOCAL_VARIABLES[$identifier]['types'],array('Null','Undefined'))));
	if (count($temp)!=0) return $temp[0]; // We'll assume the first set type is the actual type
	return '!Object';
}

/**
 * Add a type to the list of used types for a variable.
 *
 * @param  string			The variable name
 * @param  string			The type
 */
function js_set_ocportal_type($identifier,$type)
{
	global $JS_LOCAL_VARIABLES;
	$JS_LOCAL_VARIABLES[$identifier]['types'][]=$type;
}

/**
 * Add a reference to a named variable.
 *
 * @param  string				The variable name
 * @param  integer			Where the first mention of the variable is
 * @param  boolean			Whether this is an instantation reference
 * @param  boolean			Whether this is a reference (as opposed to instantiation/setting)
 * @param  ?string			The result-type (NULL: not a function)
 */
function js_add_variable_reference($identifier,$first_mention,$instantiation=true,$reference=false,$function_return=NULL)
{
	global $JS_LOCAL_VARIABLES;
	if (!isset($JS_LOCAL_VARIABLES[$identifier]))
	{
		$JS_LOCAL_VARIABLES[$identifier]=array('function_return'=>$function_return,'is_global'=>false,'types'=>array(),'unused_value'=>!$reference && !$instantiation,'first_mention'=>$first_mention);
		if ((!$instantiation) && ($identifier!='__return'))
		{
// Reenable this if desired - but it's too strict for most uses
//			js_log_warning('CHECKER','A variable/function ('.$identifier.') was used without being declared',$first_mention);
		}
	} else
	{
		$JS_LOCAL_VARIABLES[$identifier]['unused_value']=!$reference && !$instantiation;
	}
}

/**
 * If the given expression is a direct variable expression, this function will infer the type as the given type. This therefore allows type infering on usage as well as on assignment.
 *
 * @param  string				The type
 * @param  list				The expression
 */
function js_infer_expression_type_to_variable_type($_,$_)
{
/*	if (($expression[0]=='VARIABLE') && (count($expression[1][2])==0))
	{
		$identifier=$expression[1][1];
		js_set_ocportal_type($identifier,$type);
	}*/
}

/**
 * Do type checking for something specific.
 *
 * @param  list				List of allowed types
 * @param  string				Actual type involved
 * @param  integer			Current parse position
 * @param  ?string			Specific error message to give (NULL: use default)
 * @return boolean			Whether it type-checks
 */
function js_ensure_type($_allowed_types,$actual_type,$pos,$alt_error=NULL)
{
	if (($actual_type=='!Object')) return true; // We can't check it

	global $JS_PROTOTYPES;

	// Tidy up our allow list to be a nice map
	$allowed_types=array('Undefined'=>1,'Null'=>1);
	foreach ($_allowed_types as $type)
	{
		if ($type=='') continue; // Weird

		if ($type[0]=='!')
		{
			$allowed_types+=$JS_PROTOTYPES[substr($type,1)][2];
			$allowed_types[substr($type,1)]=1;
		} else
		{
			$allowed_types[$type]=1;
		}
	}

	// The check
	if (substr($actual_type,0,1)=='!') $actual_type=substr($actual_type,1);
	if (isset($allowed_types[$actual_type])) return true;

	js_log_warning('CHECKER',is_null($alt_error)?'Type mismatch':$alt_error,$pos);
	return false;
}

