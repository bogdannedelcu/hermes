<?php 
require_once('header.php'); 
require_once('dialogs.php');

echo $data['output'];
priceDialog();
contractDialog();
verifyMDDialog();

require_once('footer.php');
?>
<script src="/telerik/js/jszip.min.js"></script>
<style>
body {
	overflow:hidden;
}
</style>

</body>
</html>
