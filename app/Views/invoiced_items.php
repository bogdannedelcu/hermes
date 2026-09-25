<?php 
require_once('header.php'); 
require_once('dialogs.php');

echo $data['output'];
invoicedItemsDialog($data['invoice_id'],$data['customer_id'], $data['zone_id'], $data['total_ea']);

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
