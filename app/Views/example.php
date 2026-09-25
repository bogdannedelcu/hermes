<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8" />

<?php 
foreach($css_files as $file): ?>
 <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />

<?php endforeach; ?>
<?php foreach($js_files as $file): ?>

 <script src="<?php echo $file; ?>"></script>
<?php endforeach; ?>

<style type='text/css'>
body
{
 font-family: Arial;
 font-size: 14px;
}
</style>
</head>
<body>
<!-- Beginning header -->
 <div>
     <a href='<?php echo site_url('example/customers')?>'>Customers</a>
 </div>
<!-- End of header-->

 <!-- Beginning of main content -->
 <div style='height:20px;'></div> 
 <div style='padding: 10px;'>
     <?php echo $output; ?>

 </div>
 <!-- End of main content -->

<!-- Beginning footer -->

<!-- End of Footer -->
</body>
</html>
