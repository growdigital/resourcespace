<?php
include_once dirname(__FILE__) . "/../include/tms_link_functions.php";


function HookTms_linkAllInitialise()
    {
    $tms_link_config = get_plugin_config('tms_link');

    if(is_null($tms_link_config) || get_sysvar(TMS_LINK_MODULES_MIGRATED) !== false)
        {
        return;
        }

    $new_module = array(
        'tms_uid_field' => ''
    );
    $config_migration_map = array(
        'tms_link_table_name' => 'module_name',
        'tms_link_object_id_field' => 'rs_uid_field',
        'tms_link_checksum_field' => 'checksum_field',
        'tms_link_resource_types' => 'applicable_resource_types',
        
    );

    foreach($tms_link_config as $name => $value)
        {
        if(!array_key_exists($name, $config_migration_map))
            {
            continue;
            }

        $new_module[$config_migration_map[$name]] = $value;
        }

    $tms_rs_mappings = array();
    foreach(unserialize(base64_decode($GLOBALS['tms_link_field_mappings_saved'])) as $tms_column => $rs_field)
        {
        $new_column = array(
            'tms_column' => $tms_column,
            'rs_field' => $rs_field,
        );

        $new_column['encoding'] = 'UTF-8';
        if(in_array($tms_column, $GLOBALS['tms_link_text_columns']))
            {
            $new_column['encoding'] = 'UTF-16';
            }

        $tms_rs_mappings[] = $new_column;
        }
    $new_module['tms_rs_mappings'] = $tms_rs_mappings;
    $tms_link_modules_mappings[uniqid()] = $new_module;

    $tms_link_config['tms_link_modules_saved_mappings'] = base64_encode(serialize($tms_link_modules_mappings));

    set_plugin_config('tms_link', $tms_link_config);

    set_sysvar(TMS_LINK_MODULES_MIGRATED, 1);

    return;
    }


function HookTms_linkAllUpdate_field($resource,$field,$value,$existing)
        {
	global $tms_link_object_id_field,$tms_link_resource_types,$lang,$tms_link_field_mappings_saved;
        $resdata=get_resource_data($resource);
        if(!in_array($resdata["resource_type"],$tms_link_resource_types)){return false;}
	
	if($resource<0 || $field!=$tms_link_object_id_field){return false;}

        $tms_object_id=intval($value);
        $tmsdata=tms_link_get_tms_data($resource,$tms_object_id);

        // Update resource with TMS data
        $tms_link_field_mappings=unserialize(base64_decode($tms_link_field_mappings_saved));
        debug("tms_link: updating resource id #" . $resource);
        foreach($tms_link_field_mappings as $tms_link_column_name=>$tms_link_field_id)
                {
                if($tms_link_field_id!="" && $tms_link_field_id!=0 && isset($tmsdata[$tms_link_column_name]) && ($tms_link_field_id!=$tms_link_object_id_field))
                        {
                        debug("tms_link: updating field " . $field  . " with data from column " . $tms_link_column_name  . " for resource id #" . $resource);
                        update_field($resource,$tms_link_field_id,escape_check($tmsdata[$tms_link_column_name]));
                        }
                }
		tms_link_check_preview($resource);
        return true;
        }


function HookTms_linkAllAfterpreviewcreation($ref, $alternative=-1)
    {
    tms_link_check_preview($ref, $alternative);
    }