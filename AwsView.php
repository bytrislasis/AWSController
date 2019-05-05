<?php
$a = new \App\Http\Controllers\AWSController();
$a->AwsConnect();
$a->AwsCreateBucket("bytrislasis",'private');
$a->AwsFtpDirectoryUpload("upload/","coinofis.com");
$a->AwsGetDownloadLink();
?>
