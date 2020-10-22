<?php
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Starting migrating themes to collections using parent structure...");
$featured_collections = sql_query("SELECT * FROM collection WHERE public = 1 AND length(theme) > 0");
foreach($featured_collections as $collection)
    {
    // Ensure the full tree structure exists first to support this.
    $parent = null;
    for($level = 1; $level <= $theme_category_levels; $level++)
        {
        $col = "theme" . ($level == 1 ? "" : $level);

        if(trim($collection[$col]) == "")
            {
            continue;
            }

        $parent_sql_val = sql_is_null_or_eq_val((string) $parent, is_null($parent));
        $new_fc_name = escape_check($collection[$col]);

        logScript("Processing collection #{$collection["ref"]} - column {$col} = '{$collection[$col]}' and parent {$parent_sql_val}");

        $fc_ref = sql_value(
            sprintf("SELECT ref AS `value` FROM collection WHERE `name` = '%s' AND public = 1 AND `type` = '%s' AND parent %s",
                $new_fc_name,               // name
                COLLECTION_TYPE_FEATURED,   // type
                $parent_sql_val             // parent
            ), null);

        if(is_null($fc_ref))
            {
            $sql = sprintf("INSERT INTO collection(name, public, type, parent, thumbnail_selection_method) VALUES ('%s', 1, '%s', %s, %s)",
                $new_fc_name,
                COLLECTION_TYPE_FEATURED,
                sql_null_or_val((string) $parent, is_null($parent)),
                (!$collection_commenting ? $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"] : $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"])
            );
            logScript($sql);
            sql_query($sql);
            $fc_ref = sql_insert_id();
            logScript("Created new FC #{$fc_ref}");
            }

        // Set the parent to this collection as we descend down the tree
        $parent = $fc_ref;
        }
    
    // The necessary parts of the tree now exist to support this collection. Drop it into the tree.
    logScript("Update collection parent for the actual collection: {$collection["ref"]} with parent '$parent'");
    sql_query(sprintf("UPDATE collection SET `type` = '%s', parent = %s, thumbnail_selection_method = '%s' WHERE ref = '%s'",
        COLLECTION_TYPE_FEATURED,
        sql_null_or_val((string) $parent, is_null($parent)),
        (!$collection_commenting ? $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"] : $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["manual"]),
        $collection["ref"]        
    ));
    }

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Successfully migrated themes to collections using the parent structure");