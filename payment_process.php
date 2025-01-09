<?php
ob_start();
session_start();
require_once('admin/inc/config.php');

$error_message = '';

// Fetch the PayPal email from settings (still retained in case needed for future purposes)
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
	$paypal_email = $row['paypal_email'];
}

$payment_date = date('Y-m-d H:i:s');

// Insert payment details into the database
$statement = $pdo->prepare("INSERT INTO tbl_payment (
	customer_id,
	customer_name,
	customer_email,
	payment_date,
	txnid, 
	paid_amount,
	card_number,
    card_cvv,
    card_month,
    card_year,
    bank_transaction_info,
    payment_method,
	payment_status,
	shipping_status,
	payment_id
	) 
	VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$sql = $statement->execute(array(
	$_SESSION['customer']['cust_id'],
	$_SESSION['customer']['cust_name'],
	$_SESSION['customer']['cust_email'],
	$payment_date,
	'', // txnid is empty since no PayPal transaction
	$_POST['final_total'],
	'',
	'',
	'',
	'',
	'',
	'Walk-in',
	'Pending',
	'Pending',
	time()
));

// Process the cart and update orders
$i = 0;
foreach ($_SESSION['cart_p_id'] as $key => $value) {
	$i++;
	$arr_cart_p_id[$i] = $value;
}

$i = 0;
foreach ($_SESSION['cart_p_name'] as $key => $value) {
	$i++;
	$arr_cart_p_name[$i] = $value;
}

$i = 0;
foreach ($_SESSION['cart_size_name'] as $key => $value) {
	$i++;
	$arr_cart_size_name[$i] = $value;
}

$i = 0;
foreach ($_SESSION['cart_color_name'] as $key => $value) {
	$i++;
	$arr_cart_color_name[$i] = $value;
}

$i = 0;
foreach ($_SESSION['cart_p_qty'] as $key => $value) {
	$i++;
	$arr_cart_p_qty[$i] = $value;
}

$i = 0;
foreach ($_SESSION['cart_p_current_price'] as $key => $value) {
	$i++;
	$arr_cart_p_current_price[$i] = $value;
}

$i = 0;
$statement = $pdo->prepare("SELECT * FROM tbl_product");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
foreach ($result as $row) {
	$i++;
	$arr_p_id[$i] = $row['p_id'];
	$arr_p_qty[$i] = $row['p_qty'];
}

for ($i = 1; $i <= count($arr_cart_p_name); $i++) {
	$statement = $pdo->prepare("INSERT INTO tbl_order (
		product_id,
		product_name,
		size, 
		color,
		quantity, 
		unit_price, 
		payment_id
		) 
		VALUES (?,?,?,?,?,?,?)");
	$statement->execute(array(
		$arr_cart_p_id[$i],
		$arr_cart_p_name[$i],
		$arr_cart_size_name[$i],
		$arr_cart_color_name[$i],
		$arr_cart_p_qty[$i],
		$arr_cart_p_current_price[$i],
		time()
	));

	// Update the stock
	for ($j = 1; $j <= count($arr_p_id); $j++) {
		if ($arr_p_id[$j] == $arr_cart_p_id[$i]) {
			$current_qty = $arr_p_qty[$j];
			break;
		}
	}
	$final_quantity = $current_qty - $arr_cart_p_qty[$i];
	$statement = $pdo->prepare("UPDATE tbl_product SET p_qty=? WHERE p_id=?");
	$statement->execute(array($final_quantity, $arr_cart_p_id[$i]));
}

// Clear the cart session
unset($_SESSION['cart_p_id']);
unset($_SESSION['cart_size_id']);
unset($_SESSION['cart_size_name']);
unset($_SESSION['cart_color_id']);
unset($_SESSION['cart_color_name']);
unset($_SESSION['cart_p_qty']);
unset($_SESSION['cart_p_current_price']);
unset($_SESSION['cart_p_name']);
unset($_SESSION['cart_p_featured_photo']);

// Redirect to payment success page
header('Location: payment_success.php');
exit();
?>
