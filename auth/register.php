<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Include the database connection
require_once '../config/db_connect.php';

$error = '';
$success = '';

// 2. Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // 3. Hash the password securely using PHP's built-in bcrypt
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 4. Generate a unique User ID (e.g., WAR-5F4A9B2C)
        $user_id = 'WAR-' . strtoupper(substr(uniqid(), -8));

        // 5. Use Prepared Statements to prevent SQL Injection
        $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user_id, $username, $email, $hashed_password);

        try {
            if ($stmt->execute()) {
                $new_db_id = $conn->insert_id;

                // 6. Create an empty profile row linked to this new user
                $stmt_profile = $conn->prepare("INSERT INTO profiles (user_id) VALUES (?)");
                $stmt_profile->bind_param("i", $new_db_id);
                $stmt_profile->execute();

                // 7. Log the registration activity
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $webpage    = "/auth/register.php";
                $log_stmt   = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
                $log_stmt->bind_param("sss", $webpage, $username, $ip_address);
                $log_stmt->execute();

                $success = "Registration successful! You can now login.";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $error = "That username or email is already taken. Please choose another.";
            } else {
                $error = "A system error occurred. Please try again later.";
            }
        }
    }
}

include '../includes/header.php'; 
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap');

  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
  }

  body > .container,
  body > .container-fluid {
    padding: 0 !important;
    margin: 0 !important;
    max-width: 100% !important;
  }

  .battle-page {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: linear-gradient(160deg, #f0ebe0 0%, #e8dfc9 50%, #ddd0b5 100%);
    padding-top: 56px;
  }

  .bg-scene {
    position: absolute;
    inset: 0;
    width: 100%; height: 100%;
    z-index: 1;
    pointer-events: none;
    opacity: 0.1;
  }

  .battle-page::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 60%, transparent 30%, rgba(80,55,25,0.22) 100%);
    z-index: 2;
    pointer-events: none;
  }

  .login-wrap {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 440px;
  }

  .login-card {
    background: rgba(255,253,248,0.94);
    border: 1px solid rgba(170,145,100,0.22);
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05), 0 14px 44px rgba(0,0,0,0.12);
    overflow: hidden;
  }

  .card-head {
    background: #1c1c1c;
    padding: 1.35rem 2rem;
    text-align: center;
    position: relative;
  }
  .card-head::after {
    content: '';
    position: absolute;
    bottom: 0; left: 8%; right: 8%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #8b2500, #c0392b, #8b2500, transparent);
  }
  .card-head h4 {
    font-family: 'Cinzel', serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #f0e8d8;
    margin: 0;
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  .card-body-inner { padding: 1.5rem 2rem 1.2rem; }

  .field-label {
    display: block;
    font-family: 'Cinzel', serif;
    font-size: 0.6rem;
    font-weight: 600;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #6b5a42;
    margin-bottom: 0.4rem;
  }

  .field-hint {
    display: block;
    font-family: 'Inter', sans-serif;
    font-size: 0.7rem;
    color: #9a8a72;
    margin-top: -0.7rem;
    margin-bottom: 0.85rem;
  }

  .field-input {
    display: block;
    width: 100%;
    background: #faf8f4;
    border: 1px solid rgba(155,130,90,0.28);
    border-radius: 3px;
    padding: 0.65rem 0.85rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.92rem;
    color: #1c1c1c;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    margin-bottom: 0.9rem;
  }
  .field-input:focus {
    border-color: rgba(192,57,43,0.4);
    box-shadow: 0 0 0 3px rgba(192,57,43,0.06);
    background: #fff;
  }

  .btn-login {
    display: block;
    width: 100%;
    background: #1c1c1c;
    color: #f0e8d8;
    border: none;
    border-radius: 3px;
    padding: 0.75rem;
    font-family: 'Cinzel', serif;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    cursor: pointer;
    margin-top: 0.3rem;
    transition: background 0.22s, box-shadow 0.22s, transform 0.12s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18);
  }
  .btn-login:hover {
    background: #8b2500;
    box-shadow: 0 4px 20px rgba(139,37,0,0.3);
    transform: translateY(-1px);
  }
  .btn-login:active { transform: scale(0.99); }

  .card-divider { border: none; border-top: 1px solid rgba(160,135,95,0.14); margin: 0; }

  .card-foot {
    padding: 0.9rem 2rem;
    text-align: center;
    background: rgba(245,240,228,0.55);
  }
  .card-foot p {
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem;
    color: #8a7a65;
    margin: 0;
  }
  .card-foot a {
    color: #8b2500;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s;
  }
  .card-foot a:hover { color: #c0392b; }

  .field-alert {
    border-radius: 3px;
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem;
    padding: 0.5rem 0.75rem;
    margin-bottom: 1rem;
  }
  .field-alert-error {
    background: rgba(192,57,43,0.07);
    border: 1px solid rgba(192,57,43,0.2);
    color: #8b2500;
  }
  .field-alert-success {
    background: rgba(40,120,60,0.07);
    border: 1px solid rgba(40,120,60,0.25);
    color: #1a5c30;
  }

  footer, .footer { position: relative; z-index: 20; }
</style>

<div class="battle-page">

  <!-- Castle silhouette background (identical to login) -->
  <svg class="bg-scene" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="720" cy="580" rx="900" ry="260" fill="#7a3e00" opacity="0.4"/>
    <polygon points="0,430 170,210 340,430" fill="#5a3010"/>
    <polygon points="220,430 430,165 640,430" fill="#4a2808"/>
    <polygon points="490,430 700,185 910,430" fill="#5a3010"/>
    <polygon points="800,430 1020,155 1240,430" fill="#4a2808"/>
    <polygon points="1100,430 1300,210 1440,380 1440,430" fill="#5a3010"/>
    <rect x="0" y="430" width="1440" height="170" fill="#2e1800"/>
    <g fill="#1e1000">
      <rect x="585" y="155" width="270" height="275"/>
      <rect x="580" y="143" width="21" height="21"/><rect x="607" y="143" width="21" height="21"/>
      <rect x="634" y="143" width="21" height="21"/><rect x="661" y="143" width="21" height="21"/>
      <rect x="688" y="143" width="21" height="21"/><rect x="715" y="143" width="21" height="21"/>
      <rect x="742" y="143" width="21" height="21"/><rect x="769" y="143" width="21" height="21"/>
      <rect x="796" y="143" width="21" height="21"/>
      <rect x="525" y="200" width="78" height="230"/>
      <rect x="520" y="188" width="17" height="17"/><rect x="541" y="188" width="17" height="17"/>
      <rect x="562" y="188" width="17" height="17"/><rect x="583" y="188" width="17" height="17"/>
      <rect x="837" y="200" width="78" height="230"/>
      <rect x="832" y="188" width="17" height="17"/><rect x="853" y="188" width="17" height="17"/>
      <rect x="874" y="188" width="17" height="17"/><rect x="895" y="188" width="17" height="17"/>
      <rect x="685" y="310" width="70" height="120"/>
      <ellipse cx="720" cy="310" rx="35" ry="25" fill="#100800"/>
      <rect x="559" y="143" width="3" height="48"/>
      <polygon points="562,143 592,155 562,167" fill="#8b0000"/>
      <rect x="874" y="143" width="3" height="48"/>
      <polygon points="877,143 907,155 877,167" fill="#8b0000"/>
      <rect x="718" y="105" width="3" height="48"/>
      <polygon points="721,105 754,118 721,131" fill="#8b0000"/>
      <rect x="615" y="195" width="22" height="32"/><rect x="662" y="195" width="22" height="32"/>
      <rect x="756" y="195" width="22" height="32"/><rect x="803" y="195" width="22" height="32"/>
      <rect x="635" y="268" width="18" height="26"/><rect x="787" y="268" width="18" height="26"/>
    </g>
    <g fill="#180e00">
      <rect x="295" y="345" width="250" height="85"/>
      <rect x="290" y="334" width="17" height="17"/><rect x="311" y="334" width="17" height="17"/>
      <rect x="332" y="334" width="17" height="17"/><rect x="353" y="334" width="17" height="17"/>
      <rect x="374" y="334" width="17" height="17"/><rect x="395" y="334" width="17" height="17"/>
      <rect x="416" y="334" width="17" height="17"/><rect x="437" y="334" width="17" height="17"/>
      <rect x="458" y="334" width="17" height="17"/><rect x="479" y="334" width="17" height="17"/>
      <rect x="500" y="334" width="17" height="17"/>
      <rect x="268" y="295" width="55" height="135"/>
      <rect x="263" y="284" width="15" height="15"/><rect x="280" y="284" width="15" height="15"/>
      <rect x="297" y="284" width="15" height="15"/><rect x="314" y="284" width="15" height="15"/>
    </g>
    <g fill="#180e00">
      <rect x="895" y="345" width="250" height="85"/>
      <rect x="890" y="334" width="17" height="17"/><rect x="911" y="334" width="17" height="17"/>
      <rect x="932" y="334" width="17" height="17"/><rect x="953" y="334" width="17" height="17"/>
      <rect x="974" y="334" width="17" height="17"/><rect x="995" y="334" width="17" height="17"/>
      <rect x="1016" y="334" width="17" height="17"/><rect x="1037" y="334" width="17" height="17"/>
      <rect x="1058" y="334" width="17" height="17"/><rect x="1079" y="334" width="17" height="17"/>
      <rect x="1100" y="334" width="17" height="17"/>
      <rect x="1117" y="295" width="55" height="135"/>
      <rect x="1112" y="284" width="15" height="15"/><rect x="1129" y="284" width="15" height="15"/>
      <rect x="1146" y="284" width="15" height="15"/><rect x="1163" y="284" width="15" height="15"/>
    </g>
    <ellipse cx="720" cy="432" rx="210" ry="16" fill="#110a00" opacity="0.5"/>
    <g fill="#120800" opacity="0.5">
      <rect x="55" y="385" width="10" height="48"/><circle cx="60" cy="380" r="7"/><rect x="63" y="350" width="2" height="32"/>
      <rect x="84" y="381" width="10" height="52"/><circle cx="89" cy="376" r="7"/><rect x="92" y="346" width="2" height="32"/>
      <rect x="113" y="387" width="10" height="46"/><circle cx="118" cy="382" r="7"/><rect x="121" y="352" width="2" height="32"/>
      <rect x="142" y="383" width="10" height="50"/><circle cx="147" cy="378" r="7"/><rect x="150" y="348" width="2" height="32"/>
      <rect x="171" y="385" width="10" height="48"/><circle cx="176" cy="380" r="7"/><rect x="179" y="350" width="2" height="32"/>
    </g>
    <g fill="#120800" opacity="0.5">
      <rect x="1225" y="385" width="10" height="48"/><circle cx="1230" cy="380" r="7"/><rect x="1233" y="350" width="2" height="32"/>
      <rect x="1254" y="381" width="10" height="52"/><circle cx="1259" cy="376" r="7"/><rect x="1262" y="346" width="2" height="32"/>
      <rect x="1283" y="387" width="10" height="46"/><circle cx="1288" cy="382" r="7"/><rect x="1291" y="352" width="2" height="32"/>
      <rect x="1312" y="383" width="10" height="50"/><circle cx="1317" cy="378" r="7"/><rect x="1320" y="348" width="2" height="32"/>
      <rect x="1341" y="385" width="10" height="48"/><circle cx="1346" cy="380" r="7"/><rect x="1349" y="350" width="2" height="32"/>
    </g>
  </svg>

  <div class="login-wrap">
    <div class="login-card">

      <div class="card-head">
        <h4>⚔&nbsp;&nbsp;Join TransactiWar&nbsp;&nbsp;⚔</h4>
      </div>

      <div class="card-body-inner">

        <?php if ($error): ?>
          <div class="field-alert field-alert-error">⚠ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="field-alert field-alert-success">✔ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">

          <label class="field-label" for="username">Username</label>
          <input type="text" class="field-input" id="username" name="username" placeholder="Choose a unique name" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
          <span class="field-hint">Your username must be unique.</span>

          <label class="field-label" for="email">Email Address</label>
          <input type="email" class="field-input" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

          <label class="field-label" for="password">Password</label>
          <input type="password" class="field-input" id="password" name="password" placeholder="Create a password" required>

          <button type="submit" class="btn-login">Create Account</button>

        </form>
      </div>

      <hr class="card-divider">

      <div class="card-foot">
        <p>Already enlisted? <a href="login.php">Login here</a></p>
      </div>

    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>