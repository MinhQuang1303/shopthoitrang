<?php
/**
 * MoMo Payment Result Page
 *
 * This is the page the user is redirected to after completing the payment on the MoMo page.
 * It validates the signature from the URL parameters and displays the result to the user.
 */

header('Content-type: text/html; charset=utf-8');
require_once(__DIR__ . '/Momo.php');

$result = '';
$resultData = $_GET;
$isValid = false;
$rawHashDebug = ''; // For debugging
$partnerSignatureDebug = ''; // For debugging
$m2signatureDebug = ''; // For debugging

try {
    if (empty($_GET)) {
        throw new Exception("No payment data returned.");
    }

    $momo = new Momo();
    $isValid = $momo->validateReturnSignature($_GET);
    $m2signatureDebug = $_GET["signature"] ?? 'N/A';

    if ($isValid) {
        $resultCode = $_GET["resultCode"];
        $message = $_GET["message"];
        
        if ($resultCode == '0') {
            $result = '<div class="alert alert-success"><strong>Payment status: </strong>Success</div>';
            // You can update the database here as well, but IPN is the preferred way.
            // This page confirms to the USER. The IPN confirms to your SERVER.
        } else {
            $result = '<div class="alert alert-danger"><strong>Payment status: </strong>' . htmlspecialchars($message) . '</div>';
        }
    } else {
        $result = '<div class="alert alert-danger"><strong>Invalid Transaction:</strong> This transaction could be hacked, signature does not match.</div>';
    }

} catch (Exception $e) {
    $result = '<div class="alert alert-danger"><strong>Error:</strong> ' . $e->getMessage() . '</div>';
}

// --- Debugging Info ---
// Re-build hash for debugging display (safe to remove in production)
if (!empty($_GET)) {
    $rawHashDebug = "accessKey=" . ($_GET["accessKey"]??"") .
        "&amount=" . ($_GET["amount"]??"") .
        "&extraData=" . ($_GET["extraData"]??"") .
        "&message=" . ($_GET["message"]??"") .
        "&orderId=" . ($_GET["orderId"]??"") .
        "&orderInfo=" . ($_GET["orderInfo"]??"") .
        "&orderType=" . ($_GET["orderType"]??"") .
        "&partnerCode=" . ($_GET["partnerCode"]??"") .
        "&payType=" . ($_GET["payType"]??"") .
        "&requestId=" . ($_GET["requestId"]??"") .
        "&responseTime=" . ($_GET["responseTime"]??"") .
        "&resultCode=" . ($_GET["resultCode"]??"") .
        "&transId=" . ($_GET["transId"]??"");
    $partnerSignatureDebug = hash_hmac("sha256", $rawHashDebug, MOMO_SECRET_KEY);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>MoMo Sandbox - Payment Result</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css"/>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h1 class="panel-title">Payment status/Kết quả thanh toán</h1>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <?php echo $result; // Display Success/Failure message ?>
                        </div>
                    </div>

                    <!-- Display Transaction Details -->
                    <div class="row">
                        <?php foreach ($resultData as $key => $value): ?>
                            <?php if ($key == 'signature') continue; // Don't show signature in main list ?>
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label class="col-form-label"><?php echo htmlspecialchars($key); ?></label>
                                    <div class='input-group date'>
                                        <input type='text' value="<?php echo htmlspecialchars($value); ?>" class="form-control" readonly/>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <!-- !! UPDATE THIS LINK to your shop's homepage or order page !! -->
                                <a href="/" class="btn btn-primary">Back to shop...</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"> Debugger (Remove in Production)</h3>
                </div>
                <div class="panel-body">
                    <?php
                    echo '<b>SecretKey:</b><pre>' . htmlspecialchars(MOMO_SECRET_KEY) . '</pre></br>';
                    echo '<b>RawData: </b><pre>' . htmlspecialchars($rawHashDebug) . '</pre></br>';
                    echo '<b>MoMo signature: </b><pre>' . htmlspecialchars($m2signatureDebug) . '</pre></br>';
                    echo '<b>Partner signature: </b><pre>' . htmlspecialchars($partnerSignatureDebug) . '</pre></br>';

                    if ($isValid) {
                        echo '<div class="alert alert-success"><strong>INFO: </strong>Pass Checksum</div>';
                    } else {
                        echo '<div class="alert alert-danger" role="alert"> <strong>ERROR!:</strong> Fail checksum</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>