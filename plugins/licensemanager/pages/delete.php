<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";

$ref=getvalescaped("ref","");

# Check access
if (!checkperm("a")) {exit("Access denied");} # Should never arrive at this page without admin access

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
	sql_query("delete from license where ref='$ref'");
	sql_query("delete from resource_license where license='$ref'");
    $url_params = array(
        'ref'        => $ref,
        'search'     => getval('search',''),
        'order_by'   => getval('order_by',''),
        'collection' => getval('collection',''),
        'order_by'   => getval('order_by',''),
        'offset'     => getval('offset',''),
        'restypes'   => getval('restypes',''),
        'archive'    => getval('archive','')
    );
    $redirect_url = generateURL($baseurl_short . "/plugins/licesemanager/pages/list.php",$url_params);
	redirect($redirect_url);
	}
		
include "../../../include/header.php";
?>
<div class="BasicsBox">
<p><a href="<?php echo $baseurl_short?>plugins/licensemanager/pages/list.php"  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a></p>

<h1><?php echo $lang["delete_license"] ?></h1>

<form method="post" action="<?php echo $baseurl_short?>plugins/licensemanager/pages/delete.php" onSubmit="return CentralSpacePost(this,true);">
<input type=hidden name="submitted" value="true">
<input type=hidden name="ref" value="<?php echo $ref?>">
<?php generateFormToken("licensemanager_delete"); ?>

<div class="Question"><label><?php echo $lang["license_id"]?></label><div class="Fixed"><?php echo htmlspecialchars($ref)?></div>
<div class="clearerleft"> </div></div>


<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="delete" type="submit" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../../include/footer.php";
?>
