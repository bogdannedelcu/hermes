<?php
require_once('header.php'); 
require_once('dialogs.php');

	
echo $data['output'];
podDialog();


require_once('footer.php');
?>

<!-- Include JSZip to enable Excel Export-->
<script src="/telerik/js/jszip.min.js"></script>
<style>
body {
	overflow:hidden;
}
</style> 
</body>
</html>
