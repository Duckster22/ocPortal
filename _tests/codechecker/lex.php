<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		code_quality
 */

// These are standalone lexer tokens: finding them doesn't affect the lexing state
// ===============================================================================

global $PTOKENS;

// Logical combinators
$PTOKENS['BOOLEAN_AND']='&&';
$PTOKENS['BOOLEAN_OR']='||';
$PTOKENS['BOOLEAN_XOR']='XOR';
$PTOKENS['BOOLEAN_OR_2']='OR'; // Used by convention for error handling
// Logical comparators
$PTOKENS['IS_EQUAL']='==';
$PTOKENS['IS_GREATER']='>';
$PTOKENS['IS_SMALLER']='<';
$PTOKENS['IS_GREATER_OR_EQUAL']='>=';
$PTOKENS['IS_SMALLER_OR_EQUAL']='<=';
$PTOKENS['IS_IDENTICAL']='===';
$PTOKENS['IS_NOT_EQUAL']='!=';
$PTOKENS['IS_NOT_IDENTICAL']='!==';
// Unary logical operators
$PTOKENS['BOOLEAN_NOT']='!';
// Logical commands
$PTOKENS['IF']='if';
$PTOKENS['ELSE']='else';
$PTOKENS['ELSEIF']='elseif';
$PTOKENS['SWITCH']='switch';
$PTOKENS['CASE']='case';
$PTOKENS['DEFAULT']='default';
// Assignment
$PTOKENS['CONCAT_EQUAL']='.=';
$PTOKENS['DIV_EQUAL']='/=';
$PTOKENS['MINUS_EQUAL']='-=';
$PTOKENS['MUL_EQUAL']='*=';
$PTOKENS['PLUS_EQUAL']='+=';
$PTOKENS['EQUAL']='=';
$PTOKENS['BOR_EQUAL']='|=';
// General structural
$PTOKENS['SUPRESS_ERROR']='@';
$PTOKENS['COLON']=':';
$PTOKENS['QUESTION']='?';
$PTOKENS['COMMA']=',';
$PTOKENS['CURLY_CLOSE']='}';
$PTOKENS['CURLY_OPEN']='{';
$PTOKENS['BRACKET_OPEN']='(';
$PTOKENS['BRACKET_CLOSE']=')';
$PTOKENS['COMMAND_TERMINATE']=';';
$PTOKENS['EXTRACT_OPEN']='[';
$PTOKENS['EXTRACT_CLOSE']=']';
// Loops
$PTOKENS['FOREACH']='foreach';
$PTOKENS['AS']='as';
$PTOKENS['BREAK']='break';
$PTOKENS['CONTINUE']='continue';
$PTOKENS['FOR']='for';
$PTOKENS['WHILE']='while';
$PTOKENS['DO']='do';
// Casts
$PTOKENS['INTEGER']='integer';
$PTOKENS['BOOLEAN']='boolean';
$PTOKENS['FLOAT']='float';
$PTOKENS['ARRAY']='array';
$PTOKENS['OBJECT']='object';
$PTOKENS['STRING']='string';
// Unary operators
$PTOKENS['DEC']='--';
$PTOKENS['INC']='++';
$PTOKENS['REFERENCE']='&';
// Binary operators
$PTOKENS['BW_XOR']='^';
$PTOKENS['BW_OR']='|';
$PTOKENS['BW_NOT']='~';
$PTOKENS['SL']='<<';
$PTOKENS['SR']='>>';
$PTOKENS['CONC']='.';
$PTOKENS['ADD']='+';
$PTOKENS['SUBTRACT']='-';
$PTOKENS['MULTIPLY']='*';
$PTOKENS['DIVIDE']='/';
$PTOKENS['REMAINDER']='%';
// Classes/objects
$PTOKENS['SCOPE']='::';
$PTOKENS['CLASS']='class';
$PTOKENS['VAR']='var';
$PTOKENS['CONST']='const';
$PTOKENS['EXTENDS']='extends';
$PTOKENS['OBJECT_OPERATOR']='->';
$PTOKENS['NEW']='new';
$PTOKENS['CLONE']='clone';
$PTOKENS['PUBLIC']='public';
$PTOKENS['PRIVATE']='private';
$PTOKENS['PROTECTED']='protected';
$PTOKENS['ABSTRACT']='abstract';
$PTOKENS['INTERFACE']='interface';
$PTOKENS['IMPLEMENTS']='implements';
// Functions
$PTOKENS['FUNCTION']='function';
$PTOKENS['RETURN']='return';
// Arrays
$PTOKENS['DOUBLE_ARROW']='=>';
$PTOKENS['LIST']='list';
$PTOKENS['ARRAY']='array';
// Other
$PTOKENS['ECHO']='echo';
$PTOKENS['GLOBAL']='global';
$PTOKENS['STATIC']='static';
$PTOKENS['TRY']='try';
$PTOKENS['CATCH']='catch';
$PTOKENS['THROW']='throw';
// Simple types
$PTOKENS['true']='true';
$PTOKENS['false']='false';
$PTOKENS['NULL']='NULL';
// None matches go to be 'IDENTIFIER'
// Also detected are: integer_literal, float_literal, string_literal, variable, comment

// Loaded lexer tokens that change the lexing state
// ================================================
$PTOKENS['DOLLAR_OPEN_CURLY_BRACES']='${';
$PTOKENS['START_HEREDOC']='<<<'; // Ending it with "END;" is implicit in the PLEXER_HEREDOC state
$PTOKENS['START_ML_COMMENT']='/*'; // Ending it with "* /" is implicit in the PLEXER_ML_COMMENT state
$PTOKENS['COMMENT']='//'; // Ending it with a new-line is implicit in the PLEXER_COMMENT state
$PTOKENS['VARIABLE']='$'; // Ending it with a non-variable-character is implicit in the PLEXER_VARIABLE state
$PTOKENS['DOUBLE_QUOTE']='"'; // Ending it with non-escaped " is implicit in PLEXER_DOUBLE_QUOTE_STRING_LITERAL state (as well as extended escaping)
$PTOKENS['SINGLE_QUOTE']='\''; // Ending it with non-escaped ' is implicit in PLEXER_SINGLE_QUOTE_STRING_LITERAL state

// Lexer states
define('PLEXER_FREE',1); // (grabs implicitly)
define('PLEXER_VARIABLE',2); // grab variable
define('PLEXER_HEREDOC',3); // grab string_literal
define('PLEXER_ML_COMMENT',4); // grab comment
define('PLEXER_COMMENT',5); // grab comment
define('PLEXER_DOUBLE_QUOTE_STRING_LITERAL',6); // grab string_literal
define('PLEXER_SINGLE_QUOTE_STRING_LITERAL',7); // grab string_literal
define('PLEXER_NUMERIC_LITERAL',8); // grab float_literal/integer_literal (supports decimal, octal, hexadecimal)
define('PLEXER_EMBEDDED_VARIABLE',9); // grab variable (and return to previous state)

// These are characters that can be used to continue an identifier lexer token (any other character starts a new token).
global $PCONTINUATIONS;
$PCONTINUATIONS=array(
							'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
							'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
							'1','2','3','4','5','6','7','8','9','0','_');
global $PCONTINUATIONS_SIMPLE;
$PCONTINUATIONS_SIMPLE=array(
							'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
							'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
							'_');
// For non-identifier tokens, tokenisation is driven purely upon "best match".

function lex($text=NULL)
{
	global $PCONTINUATIONS,$PCONTINUATIONS_SIMPLE,$PTOKENS,$TEXT;

	if (!is_null($text)) $TEXT=$text;

	// ocPortal doesn't make use of this, so no need to understand it
	$matches=array();
	if (strpos($TEXT,'<'.'?php')===false)
	{
		if (strpos($TEXT,'<'.'?')!==false)
		{
			if (strpos($TEXT,'?'.'>')!==false)
			{
				log_warning('It is best to only have one PHP code block and not to terminate it. This stops problems with white-space at the end of files.');
			} else $TEXT.='?'.'>';

			log_warning('Use "<'.'?php" tagging for compatibility.');
			$num_matches=preg_match_all('#<\\?(.*)\\?'.'>#sU',$TEXT,$matches,PREG_OFFSET_CAPTURE);
		}
		elseif (strpos($TEXT,'<'.'%')!==false)
		{
			if (strpos($TEXT,'%'.'>')!==false)
			{
				log_warning('It is best to only have one PHP code block and not to terminate it. This stops problems with white-space at the end of files.');
			} else $TEXT.='%>';

			log_warning('Use "<'.'?php" tagging for compatibility.');
			$num_matches=preg_match_all('#<%(.*)\\%>#sU',$TEXT,$matches,PREG_OFFSET_CAPTURE);
		} else $num_matches=0;
	} else
	{
		if (strpos($TEXT,'?'.'>')!==false)
		{
			log_warning('It is best to only have one PHP code block and not to terminate it. This stops problems with white-space at the end of files.');
		} else $TEXT.='?'.'>';

		$num_matches=preg_match_all('#<\\?php(.*)\\?'.'>#s',$TEXT,$matches,PREG_OFFSET_CAPTURE); // TODO: Put U back. I had to take it out because some kind of limit seemed to get hit with how much matching can be done with it on.
	}
	$new_text='';
	$between_all='';
	$extra_skipped=0;
	$last_m=NULL;
	for ($i=0;$i<$num_matches;$i++)
	{
		$m=$matches[1][$i];
		if (is_string($m))
		{
			continue;
		} else
		{
			$between=substr($TEXT,strlen($new_text)+$extra_skipped,$m[1]-5-strlen($new_text)-$extra_skipped);
			$extra_skipped+=7;
			$between_all.=$between;
			$new_text.=preg_replace('#[^\n]#s',' ',$between);
			$new_text.=$m[0];
			$last_m=$m;
		}
	}
	if (!is_null($last_m))
	{
		$between=substr($TEXT,$last_m[1]+strlen($last_m[0])+2);
		$between_all.=$between;
		$new_text.=preg_replace('#[^\n]#',' ',$between);
	}
	if ($num_matches==0)
	{
		$between_all=$TEXT;
	}
	$TEXT=$new_text;
	if ((trim($between_all)!='') && (isset($GLOBALS['FILENAME'])))
	{
		global $WITHIN_PHP;
		$WITHIN_PHP=($num_matches!=0);
		require('xhtml.php');
	}

	// So that we don't have to consider end-of-file states as much.
	$TEXT.="\n";

	$tokens=array(); // We will be lexing into this list of tokens

	$special_token_value=''; // This will be used during special lexing modes to build up the special token value being lexed
	$special_token_value_2='';
	$previous_state=NULL;

	$lex_state=PLEXER_FREE;
	$escape_flag=false; // Used for string_literal escaping
	$heredoc_simple=false;
	$heredoc_buildup=array();
	$heredoc_symbol='';

	$tokens_since_comment=0;

	$indentation=0;
	$new_line=false;
	$brace_stack=array();

	// Lex the code. Hard coded state changes occur. Understanding of tokenisation implicit. Trying to match tokens to $PTOKENS, otherwise an identifier.
	$char='';
	$i=0;
	$len=strlen($TEXT);
	while (true)
	{
		switch ($lex_state)
		{
			case PLEXER_FREE:
				// Jump over any white space in our way
				do
				{
					$previous_char=$char;
					list($reached_end,$i,$char)=plex__get_next_char($i);
					if ($reached_end) break 3;

					if ($new_line)
					{
						if ($char==' ') $indentation++;
						elseif ($char=="\t") $indentation+=3;
					}
					if ($char==chr(10))
					{
						$indentation=0;
						$new_line=true;
					}
				}
				while (trim($char)=='');
				if (/*($new_line) && */(trim($previous_char)==''))
				{
					if ($char=='{')
					{
						if (!$new_line) log_warning('Bracing error (type 2/1) ',$i,true);
						array_push($brace_stack,$indentation);
					}
					elseif ($char=='}')
					{
						if (!$new_line) log_warning('Bracing error (type 2/2) ',$i,true);
						$past_indentation=array_pop($brace_stack);
						if ($past_indentation!=$indentation) log_warning('Bracing error ('.$past_indentation.' vs '.strval($indentation).')',$i,true);
					}
				}
				if (($new_line) && ($indentation%(isset($GLOBALS['NON_TERSE'])?4:3)!=0)) log_warning('Bad indentation according to coding standards',$i,true);
				$new_line=false;

				// We need to know where our token is starting
				$i--;
				$i_current=$i;

				// Try and work out what token we're looking at next
				$maybe_applicable_tokens=$PTOKENS;
				$applicable_tokens=array();
				$token_so_far='';
				while (count($maybe_applicable_tokens)!=0)
				{
					list($reached_end,$i,$char)=plex__get_next_char($i);
					if ($reached_end) break 3;

					$token_so_far.=$char;

					// Filter out any tokens that no longer match
					foreach ($maybe_applicable_tokens as $token_name=>$token_value)
					{
						// Hasn't matched (or otherwise, may still match)
						if (substr($token_value,0,strlen($token_so_far))!=$token_so_far)
						{
							unset($maybe_applicable_tokens[$token_name]);
						} else
						{
							// Is it a perfect match?
							if ((strlen($token_so_far)==strlen($token_value)) && ((!in_array($token_so_far{0},$PCONTINUATIONS)) || (!in_array($TEXT{$i},$PCONTINUATIONS))))
							{
								$applicable_tokens[]=$token_name;
								unset($maybe_applicable_tokens[$token_name]);
							}
						}
					}
				}

				// If we have any applicable tokens, find the longest and move $i so it's as we just read it
				$i=$i_current;
				if (count($applicable_tokens)!=0)
				{
					usort($applicable_tokens,'plex__strlen_sort');
					$token_found=$applicable_tokens[count($applicable_tokens)-1];

					$i+=strlen($PTOKENS[$token_found]);

					// Is it a special state jumping token?
					if ($token_found=='VARIABLE')
					{
						$lex_state=PLEXER_VARIABLE;
						break;
					}
					elseif ($token_found=='START_HEREDOC')
					{
						$lex_state=PLEXER_HEREDOC;
						$matches=array();
						preg_match('#[A-Za-z0-9\_]*#',substr($TEXT,$i,30),$matches);
						$heredoc_symbol=$matches[0];
						$i+=strlen($heredoc_symbol);
						break;
					}
					elseif ($token_found=='START_ML_COMMENT')
					{
						$lex_state=PLEXER_ML_COMMENT;
						break;
					}
					elseif ($token_found=='COMMENT')
					{
						$lex_state=PLEXER_COMMENT;
						break;
					}
					elseif ($token_found=='DOUBLE_QUOTE')
					{
						$lex_state=PLEXER_DOUBLE_QUOTE_STRING_LITERAL;
						break;
					}
					elseif ($token_found=='SINGLE_QUOTE')
					{
						$lex_state=PLEXER_SINGLE_QUOTE_STRING_LITERAL;
						break;
					}
					else
					{
						if (!in_array($token_found,array('COMMA','DOUBLE_ARROW'))) // We don't count array definitions, etc
						{
							$tokens_since_comment++;
							if ((isset($GLOBALS['pedantic'])) && ($tokens_since_comment>200))
							{
								log_warning('Bad comment density',$i,true);
								$tokens_since_comment=0;
							}
						}
					}

					if (($token_found=='IF') && (@$tokens[count($tokens)-1][0]=='ELSE')) log_warning('Use \'elseif\' not \'else if\'',$i,true);

					/*$terse_style=!isset($GLOBALS['NON_TERSE']); TODO Maybe put back, but should be optional
					if ($terse_style)
					{
						if (($i_current>0) && ($TEXT{$i_current-1}==' ') && (in_array($token_found,array('COMMA','IS_EQUAL','IS_GREATER','IS_SMALLER','IS_GREATER_OR_EQUAL','IS_SMALLER_OR_EQUAL','IS_IDENTICAL','IS_NOT_EQUAL','IS_NOT_IDENTICAL','CONCAT_EQUAL','DIV_EQUAL','MINUS_EQUAL','MUL_EQUAL','PLUS_EQUAL','BOR_EQUAL','EQUAL','COMMA','BW_XOR','BW_OR','SL','SR','CONC','ADD','SUBTRACT','MULTIPLY','DIVIDE','REMAINDER','OBJECT_OPERATOR')))) log_warning('Superfluous spacing (for '.$token_found.') against coding standards',$i,true);
					} else
					{
						if (($i_current>0) && ($TEXT{$i_current-1}==' ') && (in_array($token_found,array('OBJECT_OPERATOR')))) log_warning('Superfluous spacing (for '.$token_found.') against coding standards',$i,true);
						if (($i_current>0) && (($TEXT{$i}!=' ') && ($TEXT{$i}!=chr(10)) && ($TEXT{$i}!=chr(13))) && (in_array($token_found,array('COMMA')))) log_warning('Missing surrounding spacing (for '.$token_found.') against coding standards',$i,true);
						if (($i_current>0) && (($TEXT{$i_current-1}!=' ') || (($TEXT{$i}!=' ') && ($TEXT{$i}!=chr(10)) && ($TEXT{$i}!=chr(13)))) && (in_array($token_found,array('IS_EQUAL','IS_GREATER','IS_SMALLER','IS_GREATER_OR_EQUAL','IS_SMALLER_OR_EQUAL','IS_IDENTICAL','IS_NOT_EQUAL','IS_NOT_IDENTICAL','CONCAT_EQUAL','DIV_EQUAL','MINUS_EQUAL','MUL_EQUAL','PLUS_EQUAL','BOR_EQUAL','EQUAL','COMMA','BW_XOR','BW_OR','SL','SR','CONC','ADD','SUBTRACT','MULTIPLY','DIVIDE','REMAINDER')))) log_warning('Missing surrounding spacing (for '.$token_found.') against coding standards',$i,true);
					}
					if (($TEXT{$i}!=' ') && ($TEXT{$i}!=chr(10)) && ($TEXT{$i}!=chr(13)) && (in_array($token_found,array('IF','ELSEIF','FOREACH','FOR','WHILE','DO')))) log_warning('Missing following spacing (for '.$token_found.') against coding standards',$i,true);
					if (($i_current>0) && (($TEXT{$i_current-1}!=' ') || (($TEXT{$i}!=' ') && ($TEXT{$i}!=chr(10)) && ($TEXT{$i}!=chr(13)))) && (in_array($token_found,array('BOOLEAN_AND','BOOLEAN_XOR','BOOLEAN_OR','BOOLEAN_OR_2')))) log_warning('Missing surrounding spacing (for '.$token_found.') against coding standards',$i,true);*/

					$tokens[]=array($token_found,$i);
				} else
				{
					// Otherwise, we've found an identifier or numerical literal token, so extract it
					$token_found='';
					$numeric=NULL;
					do
					{
						list($reached_end,$i,$char)=plex__get_next_char($i);
						if ($reached_end) break 3;
						if (is_null($numeric))
						{
							$numeric=in_array($char,array('0','1','2','3','4','5','6','7','8','9'));
						}

						if ((!in_array($char,$PCONTINUATIONS)) && (($numeric===false) || ($char!='.') || (!is_numeric($TEXT{$i})))) break;

						$token_found.=$char;
					}
					while (true);
					$i--;

					if ($numeric)
					{
						if (strpos($token_found,'.')!==false) $tokens[]=array('float_literal',floatval($token_found),$i);
						elseif (strpos($token_found,'x')!==false) $tokens[]=array('integer_literal',intval($token_found,16),$i);
						elseif ($token_found{0}=='0') $tokens[]=array('integer_literal',intval($token_found,8),$i);
						else $tokens[]=array('integer_literal',intval($token_found),$i);
					} else
					{
						if ($token_found=='')
						{
							log_warning('Bad token found',$i,true);
							exit();
						}
						$tokens[]=array('IDENTIFIER',$token_found,$i);
					}
				}

				//print_r($tokens[count($tokens)-1]);
				//echo '<br />';
				//flush();

				break;

			case PLEXER_VARIABLE:
				list($reached_end,$i,$char)=plex__get_next_char($i);
				if ($reached_end) break 2;

				// Exit case
				if (!in_array($char,$PCONTINUATIONS))
				{
					$lex_state=PLEXER_FREE;
					$tokens[]=array('variable',$special_token_value,$i);
					$special_token_value='';
					$i--;
					break;
				}

				// Normal case
				$special_token_value.=$char;

				break;


			case PLEXER_HEREDOC:
				list($reached_end,$i,$char)=plex__get_next_chars($i,strlen($heredoc_symbol)+2);

				// Exit case
				if ($char==chr(10).$heredoc_symbol.';')
				{
					$lex_state=PLEXER_FREE;
					if ((isset($GLOBALS['CHECKS'])) && (preg_match('#\<[^\<\>]*\>#',$special_token_value)!=0))
						log_warning('It looks like HTML used outside of templates',$i,true);
					$tokens[]=array('string_literal',$special_token_value,$i);
					$tokens[]=array('COMMAND_TERMINATE',$i);
					if ((isset($GLOBALS['CHECKS'])) && (isset($GLOBALS['PEDANTIC'])) && (strpos($special_token_value,'<')!==false) && (strpos($special_token_value,'<')!=strlen($special_token_value)-1)) log_warning('Should\'t this be templated?',$i,true);
					$special_token_value='';
					break;
				}
				$i-=strlen($heredoc_symbol)+1;
				if (!isset($char{0})) break 2;
				$char=$char{0};

				// Escape flag based filtering
				$actual_char=$char;
				if ($escape_flag)
				{
					if ($char=='$') $actual_char='$';
					elseif ($char=='{') $actual_char='{';
					elseif ($char=='}') $actual_char='}';
					else $actual_char='\\'.$char;
				} else
				{
					$heredoc_simple=!((($char=='{') && ($TEXT{$i}=='$')) || (($char=='$') && ($TEXT{$i}=='{')));
					if (($char=='$') || (!$heredoc_simple))
					{
						if (!$heredoc_simple) $i++;
						$tokens[]=array('string_literal',$special_token_value,$i);
						$tokens[]=array('CONC',$i);
						$special_token_value='';
						$lex_state=PLEXER_EMBEDDED_VARIABLE;
						$previous_state=PLEXER_HEREDOC;
						$heredoc_buildup=array();
						break;
					}
					elseif (($char=='\\') || ($char=='{')) $actual_char=''; // Technically we should only allow "$whatever" if whatever exists, but this future proofs checked code
				}

				// Normal case
				$special_token_value.=$actual_char;

				$escape_flag=((!$escape_flag) && ($char=='\\'));

				break;

			case PLEXER_EMBEDDED_VARIABLE:
				list($reached_end,$i,$char)=plex__get_next_char($i);
				if ($reached_end) break 2;

				if (!in_array($char,$heredoc_simple?$PCONTINUATIONS_SIMPLE:$PCONTINUATIONS))
				{
					$exit=false;

					if (!$heredoc_simple)
					{
						// Complex
						if ($char=='}')
						{
							$exit=true;
						} else
						{
							$matches=array();
							if (($char=='[') && ($TEXT{$i}=='\'') && (preg_match('#^\[\'([^\']*)\'\]#',substr($TEXT,$i-1,40),$matches)!=0)) // NOTE: Have disallowed escaping within the quotes
							{
								$heredoc_buildup[]=array((count($heredoc_buildup)==0)?'variable':'IDENTIFIER',$special_token_value_2,$i);
								$special_token_value_2='';
								$heredoc_buildup[]=array('EXTRACT_OPEN',$i);
								$heredoc_buildup[]=array('string_literal',$matches[1],$i);
								$heredoc_buildup[]=array('EXTRACT_CLOSE',$i);
								$i+=strlen($matches[1])+3;
							}
							elseif (($char=='[') && (preg_match('#^\[([A-Za-z0-9\_]+)\]#',substr($TEXT,$i-1,40),$matches)!=0))
							{
								$heredoc_buildup[]=array((count($heredoc_buildup)==0)?'variable':'IDENTIFIER',$special_token_value_2,$i);
								$special_token_value_2='';
								$heredoc_buildup[]=array('EXTRACT_OPEN',$i);
								$heredoc_buildup[]=array('IDENTIFIER',$matches[1],$i);
								$heredoc_buildup[]=array('EXTRACT_CLOSE',$i);
								$i+=strlen($matches[1])+1;
							}
							elseif (($char=='-') && ($TEXT{$i}=='>'))
							{
								$heredoc_buildup[]=array((count($heredoc_buildup)==0)?'variable':'IDENTIFIER',$special_token_value_2,$i);
								$special_token_value_2='';
								$heredoc_buildup[]=array('OBJECT_OPERATOR',$i);
								$i++;
							} else
							{
								log_warning('Bad token found',$i,true);
								exit();
							}
						}
					} else
					{
						// Simple
						$matches=array();
						if (($char=='-') && ($TEXT{$i}=='>'))
						{
							$heredoc_buildup[]=array((count($heredoc_buildup)==0)?'variable':'IDENTIFIER',$special_token_value_2,$i);
							$special_token_value_2='';
							$heredoc_buildup[]=array('OBJECT_OPERATOR',$i);
							$i++;
						}
						elseif (($char=='[') && (preg_match('#^\[([\'A-Za-z0-9\_]+)\]#',substr($TEXT,$i-1,40),$matches)!=0))
						{
							if (strpos($matches[1],"'")!==false)
							{
								log_warning('Do not use quotes with the simple variable embedding syntax',$i,true);
								exit();
							}
							$heredoc_buildup[]=array((count($heredoc_buildup)==0)?'variable':'IDENTIFIER',$special_token_value_2,$i);
							$special_token_value_2='';
							$heredoc_buildup[]=array('EXTRACT_OPEN',$i);
							$heredoc_buildup[]=array('string_literal',$matches[1],$i);
							$heredoc_buildup[]=array('EXTRACT_CLOSE',$i);
							$i+=strlen($matches[1])+1;
						} else $exit=true;
					}

					if ($exit)
					{
						$lex_state=$previous_state;
						if ($special_token_value_2!='')
						{
							$heredoc_buildup[]=array((count($heredoc_buildup)==0)?'variable':'IDENTIFIER',$special_token_value_2,$i);
						}
						if (count($heredoc_buildup)>0)
						{
							$tokens[]=array('IDENTIFIER','strval',$i);
							$tokens[]=array('BRACKET_OPEN',$i);
							$tokens=array_merge($tokens,$heredoc_buildup);
							$tokens[]=array('BRACKET_CLOSE',$i);
							$tokens[]=array('CONC',$i);
						}
						$special_token_value_2='';

						if ($heredoc_simple) $i--;
						break;
					}
				} else {
					// Normal case
					$special_token_value_2.=$char;
				}

				break;

			case PLEXER_COMMENT:
				$tokens_since_comment=0;

				list($reached_end,$i,$char)=plex__get_next_char($i);
				if ($reached_end) break 2;

				// Exit case
				if ($char==chr(10))
				{
					$lex_state=PLEXER_FREE;
					$tokens[]=array('comment',$special_token_value,$i);
					$special_token_value='';
					$i--;
					break;
				}

				// Normal case
				$special_token_value.=$char;

				break;

			case PLEXER_ML_COMMENT:
				$tokens_since_comment=0;

				list($reached_end,$i,$char)=plex__get_next_chars($i,2);

				// Exit case
				if ($char=='*/')
				{
					$lex_state=PLEXER_FREE;
					$tokens[]=array('comment',$special_token_value,$i);
					$special_token_value='';
					break;
				}

				$i-=1;
				if (!isset($char{0})) break 2;
				$char=$char{0};

				// Normal case
				$special_token_value.=$char;

				break;

			case PLEXER_DOUBLE_QUOTE_STRING_LITERAL:
				list($reached_end,$i,$char)=plex__get_next_char($i);
				if ($reached_end) break 2;

				// Exit case
				if (($char=='"') && (!$escape_flag))
				{
					$lex_state=PLEXER_FREE;
					$tokens[]=array('string_literal',$special_token_value,$i);
					if ((isset($GLOBALS['CHECKS'])) && (isset($GLOBALS['PEDANTIC'])) && (strpos($special_token_value,'<')!==false) && (strpos($special_token_value,'<')!=strlen($special_token_value)-1)) log_warning('Should\'t this be templated?',$i,true);
					$special_token_value='';
					break;
				}

				// Escape flag based filtering
				$actual_char=$char;
				if ($escape_flag)
				{
					if ($char=='n') $actual_char="\n";
					elseif ($char=='r') $actual_char="\r";
					elseif ($char=='t') $actual_char="\t";
				} else
				{
					$heredoc_simple=!((($char=='{') && ($TEXT{$i}=='$')) || (($char=='$') && ($TEXT{$i}=='{')));
					if (($char=='$') || (!$heredoc_simple))
					{
						if (!$heredoc_simple) $i++;
						$tokens[]=array('string_literal',$special_token_value,$i);
						$tokens[]=array('CONC',$i);
						$special_token_value='';
						$lex_state=PLEXER_EMBEDDED_VARIABLE;
						$previous_state=PLEXER_DOUBLE_QUOTE_STRING_LITERAL;
						$heredoc_buildup=array();
						break;
					}
					if ($char=='\\') $actual_char='';
				}

				// Normal case
				$special_token_value.=$actual_char;

				$escape_flag=((!$escape_flag) && ($char=='\\'));

				break;

			case PLEXER_SINGLE_QUOTE_STRING_LITERAL:
				list($reached_end,$i,$char)=plex__get_next_char($i);
				if ($reached_end) break 2;

				// Exit case
				if (($char=="'") && (!$escape_flag))
				{
					$lex_state=PLEXER_FREE;
					$tokens[]=array('string_literal',$special_token_value,$i);
					if ((isset($GLOBALS['CHECKS'])) && (isset($GLOBALS['PEDANTIC'])) && (strpos($special_token_value,'<')!==false) && (strpos($special_token_value,'<')!=strlen($special_token_value)-1)) log_warning('Should\'t this be templated?',$i,true);
					$special_token_value='';
					break;
				}

				// Escape flag based filtering
				$actual_char=$char;
				if ($escape_flag)
				{
					if ($char=="'") $actual_char="'";
					elseif ($char=='\\') $actual_char='\\';
					else $actual_char='\\'.$char;
				} elseif ($char=='\\') $actual_char='';

				// Normal case
				$special_token_value.=$actual_char;

				$escape_flag=((!$escape_flag) && ($char=='\\'));

				break;
		}
	}

	return $tokens;
}

/**
 * Helper function for usort to sort a list by string length.
 *
 * @param  string			The first string to compare
 * @param  string			The second string to compare
 * @return boolean		  The comparison result
 */
function plex__strlen_sort($a,$b)
{
	global $PTOKENS;
	$a=$PTOKENS[$a];
	$b=$PTOKENS[$b];
	if ($a==$b) return 0;
	return (strlen($a)<strlen($b))?-1:1;
}

function plex__get_next_char($i)
{
	global $TEXT;
	if ($i>=strlen($TEXT)) return array(true,$i+1,'');
	$char=$TEXT{$i};
	return array(false,$i+1,$char);
}

function plex__get_next_chars($i,$num)
{
	global $TEXT;
	$str=substr($TEXT,$i,$num);
	return array(strlen($str)<$num,$i+$num,$str);
}
