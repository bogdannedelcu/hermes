<?php 

require_once('header.php'); 
require_once('dialogs.php');


echo $data['output'];

if($data['title'] == 'Clienti') customerDialog();
elseif($data['title'] == 'Contracte') contractDialog();
elseif($data['title'] == 'Servicii') serviceDialog();
elseif($data['title'] == 'Tarife') tariffPriceDialog($data['title']);
elseif($data['title'] == 'Preturi') tariffPriceDialog($data['title']);
elseif($data['title'] == 'Loturi Clienti') zonesDialog();
elseif($data['title'] == 'POD-uri') podDialog();

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