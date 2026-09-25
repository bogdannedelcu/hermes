<?php
require_once(__DIR__.'/../header.php'); 
require_once(__DIR__.'/../dialogs.php');

	
echo $data['output'];

require_once(__DIR__.'/../footer.php');
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
