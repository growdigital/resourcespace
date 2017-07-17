<?php
/*
 * API v2 functions
 *
 * Montala Limited, July 2016
 *
 * For documentation please see: http://www.resourcespace.com/knowledge-base/api/
 *
 */

function get_api_key($user)
    {
    // Return a private scramble key for this user.
    global $api_scramble_key;
    return hash("sha256", $user . $api_scramble_key);
    }

function check_api_key($username,$querystring,$sign)
    {
    // Check a query is signed correctly.
    
    // Fetch user ID and API key
    $user=get_user_by_username($username); if ($user===false) {return false;}
    $private_key=get_api_key($user);
    
    # Sign the querystring ourselves and check it matches.
    
    # First remove the sign parameter as this would not have been present when signed on the client.
    $s=strpos($querystring,"&sign=");
    if ($s===false || $s+6+strlen($sign)!==strlen($querystring)) {return false;}
    $querystring=substr($querystring,0,$s);
    
    # Calculate the expected signature.
    $expected=hash("sha256",$private_key . $querystring);
    
    # Was it what we expected?
    return $expected==$sign;
    }

function execute_api_call($query)
    {
    // Execute the specified API function.
    $params=array();parse_str($query,$params);        
    if (!array_key_exists("function",$params)) {return false;}
    $function=$params["function"];
    if (!function_exists("api_" . $function)) {return false;}
    
    $eval="return api_" . $function . "(";
    $n=1;while (true)
        {
        if (array_key_exists("param" . $n,$params))
            {
            if ($n>1) {$eval.=",";}
            $eval.="\"" . str_replace("\"","\\\"",$params["param" . $n]) . "\"";
            $n++;
            }
        else
            {
            break;
            }
        }
    $eval.=");";
    return json_encode(eval($eval));
    }
    
/**
* Get an array of all the canvases for the identifier ready for JSON encoding
* 
* @uses get_data_by_field()
* @uses get_original_imagesize()
* @uses get_resource_type_field()
* @uses get_resource_path()
* @uses iiif_get_thumbnail()
* @uses iiif_get_image()
* 
* @param integer $identifier		IIIF identifier (this associates resources via the metadata field set as $iiif_identifier_field
* @param array $iiif_results		Array of ResourceSpace search results that match the $identifier, sorted 
* @param boolean $sequencekeys		Get the array with each key matching the value set in the metadata field $iiif_sequence_field. By default the array will be sorted but have a 0 based index
* 
* @return array
*/
function iiif_get_canvases($identifier, $iiif_results,$sequencekeys=false)
    {
    global $rooturl,$rootimageurl;	
			
    $canvases = array();
    foreach ($iiif_results as $iiif_result)
        {       
		$position = $iiif_result["iiif_position"];
        $canvases[$position]["@id"] = $rooturl . $identifier . "/canvas/" . $position;
        $canvases[$position]["@type"] = "sc:Canvas";
        $canvases[$position]["label"] = (isset($position_prefix)?$position_prefix:'') . $position;
        
        // Get the size of the images
        $img_path = get_resource_path($iiif_result["ref"],true,'',false);
        
        $image_size = get_original_imagesize($iiif_result["ref"],$img_path);
        $canvases[$position]["height"] = intval($image_size[1]);
        $canvases[$position]["width"] = intval($image_size[2]);
				
		// "If the largest image�s dimensions are less than 1200 pixels on either edge, then the canvas�s dimensions should be double those of the image." - From http://iiif.io/api/presentation/2.1/#canvas
		if($image_size[1] < 1200 || $image_size[2] < 1200)
			{
			$image_size[1] = $image_size[1] * 2;
			$image_size[2] = $image_size[2] * 2;
			}
        
        $canvases[$position]["thumbnail"] = iiif_get_thumbnail($iiif_result["ref"]);
        
        // Add image (only 1 per canvas currently supported)
        $canvases[$position]["images"] = iiif_get_image($identifier,$iiif_result["ref"],$position);		
        }
    
	if($sequencekeys)
		{
		// keep the sequence identifiers as keys so a required canvas can be accessed by sequence id
		return $canvases;
		}
	
    ksort($canvases);	
    $return=array();
    foreach($canvases as $canvas)
        {
        $return[] = $canvas;
        }
    return $return;
    }

/**
* Get  thumbnail information for the specified resource id ready for IIIF JSON encoding
* 
* @uses get_resource_path()
* @uses getimagesize()
* 
* @param integer $resourceid		Resource ID
*
* @return array
*/
function iiif_get_thumbnail($resourceid)
    {
	global $rootimageurl;
	$thumbnail = array();
	$thumbnail["@id"] = $rootimageurl . $resourceid . "/full/thm/0/default.jpg";
	$thumbnail["@type"] = "dctypes:Image";
	
	 // Get the size of the images
	$img_path = get_resource_path($resourceid,true,'thm',false);
    if ((list($tw,$th) = @getimagesize($img_path))!==false)
        {
        $thumbnail["height"] = $th;
        $thumbnail["width"] = $tw;   
        }
    else
        {
        // Use defaults
        $thumbnail["height"] = 150;
        $thumbnail["width"] = 150;    
        }
            
	$thumbnail["format"] = "image/jpeg";
	
	$thumbnail["service"] =array();
	$thumbnail["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$thumbnail["service"]["@id"] = $rootimageurl;
	$thumbnail["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	return $thumbnail;
	}
	
/**
* Get the image for the specified identifier canvas and resource id
* 
* @uses get_original_imagesize()
* @uses get_resource_path()
* 
* @param integer $identifier		IIIF identifier (this associates resources via the metadata field set as $iiif_identifier_field
* @param integer $resourceid		Resource ID
* @param string $position			The canvas identifier, i..e position in the sequence. If $iiif_sequence_field is defined it will be the value of this metadata field for the given resource
* 
* @return array
*/	
function iiif_get_image($identifier,$resourceid,$position)
    {
    global $rooturl,$rootimageurl;
	$images = array();
	$images["@context"] = "http://iiif.io/api/presentation/2/context.json";
	$images["@id"] = $rooturl . $identifier . "/annotation/" . $position;
	$images["@type"] = "oa:Annotation";
	$images["motivation"] = "sc:painting";
	
	$images["resource"] = array();
	$images["resource"]["@id"] = $rootimageurl . $resourceid . "/full/max/0/default.jpg";
	$images["resource"]["@type"] = "dctypes:Image";
	$images["resource"]["format"] = "image/jpeg";
	$images["resource"]["service"] =array();
	$images["resource"]["service"]["@context"] = "http://iiif.io/api/image/2/context.json";
	$images["resource"]["service"]["@id"] = $rootimageurl;
	$images["resource"]["service"]["profile"] = "http://iiif.io/api/image/2/level1.json";
	$images["on"] = $rooturl . $identifier . "/canvas/" . $position;
	
	$img_path = get_resource_path($resourceid,true,'',false);
	$image_size = get_original_imagesize($resourceid,$img_path);
	
	$images["height"] = intval($image_size[1]);
	$images["width"] = intval($image_size[2]);
	return $images;  
	}
