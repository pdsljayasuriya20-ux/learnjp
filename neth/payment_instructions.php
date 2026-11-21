<?php
// Session එක ආරම්භ කිරීම (db_connection.php මඟින් සිදුවේ)
require_once 'db_connection.php'; 

// 1. ආරක්ෂක පියවර: Client Logged In ද යන්න පරීක්ෂා කිරීම
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    // Logged in නැත්නම්, නැවත login page එකට යොමු කරනවා.
    header("Location: login.php");
    exit();
}

$client_name = $_SESSION['client_name'] ?? 'Client';
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ගෙවීම් උපදෙස් | Nethsitha Jothishya</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
            color: #333;
            text-align: center;
        }
        .container {
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 40px auto;
            text-align: left;
        }
        h2 {
            color: #1a4d2e; 
            border-bottom: 3px solid #5a9e6d; 
            padding-bottom: 12px;
            margin-top: 0;
            text-align: center;
        }
        h3 {
            color: #4a4a4a;
            margin-top: 30px;
            border-left: 5px solid #5a9e6d;
            padding-left: 10px;
        }
        p {
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .payment-box {
            background-color: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px dashed #5a9e6d;
        }
        .payment-box strong {
            color: #1a4d2e;
            display: block;
            margin-bottom: 5px;
        }
        .highlight {
            font-size: 1.2em;
            color: #d8000c;
            font-weight: bold;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        a {
            color: #5a9e6d;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💰 ගෙවීම් උපදෙස් (Payment Instructions)</h2>
        <p>ආයුබෝවන්, **<?php echo htmlspecialchars($client_name); ?>**! ඔබගේ සේවාව ලබා ගැනීමට පෙර පහත දැක්වෙන ගෙවීම් උපදෙස් අනුගමනය කරන්න.</p>

        <hr>

        <!-- 1. ගෙවිය යුතු මුදල -->
        <h3>1. ගෙවිය යුතු මුදල:</h3>
        <p class="highlight">Rs. 5,000.00</p>
        <p>ජ්‍යොතිෂ්‍ය සේවාවට අදාළ සම්පූර්ණ ගාස්තුව මෙය වේ.</p>

        <!-- 2. බැංකු තොරතුරු -->
        <h3>2. බැංකු තොරතුරු (Bank Details):</h3>
        <div class="payment-box">
            <strong>බැංකුවේ නම:</strong> Commercial Bank of Ceylon PLC<br>
            <strong>ගිණුම් හිමිකරුගේ නම:</strong> Nethsitha Jothishya Services (Pvt) Ltd<br>
            <strong>ගිණුම් අංකය:</strong> 8000 1234 5678<br>
            <strong>ශාඛාව:</strong> Colombo Main Branch
        </div>
        
        <!-- 3. වැදගත් උපදෙස් -->
        <h3>3. වැදගත් උපදෙස්:</h3>
        
        <h4>ගෙවීම් Reference එක:</h4>
        <p>
            ඔබ ගෙවීම් කරන විට **Reference (විස්තර)** කොටසේ ඔබගේ **Email ලිපිනය** හෝ **දුරකථන අංකය** පැහැදිලිව සඳහන් කරන්න. මෙය ඔබගේ ගෙවීම් වාර්තාව නිවැරදිව තහවුරු කිරීමට අත්‍යවශ්‍ය වේ.
        </p>

        <h4>ගෙවීම් තහවුරු කිරීම:</h4>
        <p>
            ගෙවීම් සාර්ථකව සිදු කළ පසු, කරුණාකර එහි **රිසිට්පත (Receipt) හෝ ගෙවීම් තහවුරු කිරීමේ Screenshot එක** පහත සඳහන් Email ලිපිනයට එවන්න: 
            <a href="mailto:payments@nethsithajothishya.lk">payments@nethsithajothishya.lk</a>
        </p>
        <p>
            තහවුරු කිරීම ලැබුණු වහාම, අපි ඔබගේ ගිණුම සක්‍රිය කර ප්‍රතිඵල පිටුවට ප්‍රවේශය ලබා දෙමු.
        </p>

        <div class="back-link">
            <a href="dashboard.php">Dashboard වෙත ආපසු යන්න</a>
        </div>
    </div>
</body>
</html>