<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ऑर्डर की पुष्टि (Order Confirmed) — OJAS + Capsule</title>
  
  <meta name="description" content="आपका ऑर्डर सफलतापूर्वक दर्ज कर लिया गया है। OJAS + Capsule - Zaviora Healthcare.">
  <meta name="robots" content="noindex, nofollow">
  
  <link rel="icon" type="image/png" href="images/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Noto+Sans+Devanagari:wght@400;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
  
  <!-- Meta Pixel Code -->
  <script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '1030373859409592');
  fbq('track', 'PageView');
  fbq('track', 'Lead');
  </script>
  <noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=1030373859409592&ev=PageView&noscript=1"
  /></noscript>
  <!-- End Meta Pixel Code -->

  <style>
    :root {
      --saffron: #E07B2A;
      --saffron-deep: #C4611A;
      --gold: #F5C542;
      --cream: #FFF8EE;
      --dark: #1A0F00;
      --brown: #3D1F00;
      --green: #2D6A4F;
      --green-light: #52B788;
      --white: #FFFFFF;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Noto Sans Devanagari', sans-serif;
      background: var(--dark);
      color: var(--cream);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow-x: hidden;
    }

    /* Background glows */
    .glow-1 {
      position: absolute;
      top: -10%;
      left: -10%;
      width: 50vw;
      height: 50vh;
      background: radial-gradient(circle, rgba(224, 123, 42, 0.15) 0%, transparent 70%);
      pointer-events: none;
      z-index: 1;
    }
    .glow-2 {
      position: absolute;
      bottom: -10%;
      right: -10%;
      width: 50vw;
      height: 50vh;
      background: radial-gradient(circle, rgba(245, 197, 66, 0.1) 0%, transparent 70%);
      pointer-events: none;
      z-index: 1;
    }

    .thankyou-container {
      background: rgba(61, 31, 0, 0.4);
      border: 2px solid var(--gold);
      border-radius: 28px;
      width: 100%;
      max-width: 600px;
      padding: 50px 40px;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(12px);
      position: relative;
      z-index: 2;
      animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes scaleIn {
      from { transform: scale(0.9); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    /* Animated Checkmark */
    .success-checkmark {
      width: 100px;
      height: 100px;
      margin: 0 auto 30px auto;
    }
    .success-checkmark .check-icon {
      width: 100px;
      height: 100px;
      position: relative;
      border-radius: 50%;
      box-sizing: content-box;
      border: 4px solid rgba(82, 183, 136, 0.2);
    }
    .success-checkmark .check-icon::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      border: 4px solid var(--green-light);
      border-left-color: transparent;
      border-bottom-color: transparent;
      animation: rotate 1.2s ease-in-out;
      box-sizing: border-box;
    }
    .success-checkmark .check-icon .icon-line {
      height: 5px;
      background-color: var(--green-light);
      display: block;
      border-radius: 2px;
      position: absolute;
      z-index: 10;
    }
    .success-checkmark .check-icon .icon-line.line-tip {
      width: 25px;
      left: 23px;
      top: 49px;
      transform: rotate(45deg);
      animation: writeTip 0.6s ease-in-out;
    }
    .success-checkmark .check-icon .icon-line.line-long {
      width: 47px;
      right: 18px;
      top: 42px;
      transform: rotate(-45deg);
      animation: writeLong 0.6s ease-in-out;
    }

    @keyframes rotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    @keyframes writeTip {
      0% { width: 0; left: 16px; top: 38px; }
      100% { width: 25px; left: 23px; top: 49px; }
    }
    @keyframes writeLong {
      0% { width: 0; right: 46px; top: 54px; }
      100% { width: 47px; right: 18px; top: 42px; }
    }

    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      color: var(--gold);
      margin-bottom: 16px;
      font-weight: 900;
    }

    .greeting {
      font-size: 20px;
      color: var(--white);
      margin-bottom: 24px;
      font-weight: 700;
    }

    .message {
      font-size: 16px;
      line-height: 1.8;
      color: rgba(255, 248, 238, 0.8);
      margin-bottom: 30px;
    }

    .order-info-box {
      background: rgba(255, 255, 255, 0.05);
      border: 1px dashed rgba(245, 197, 66, 0.3);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 35px;
      text-align: left;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      font-size: 15px;
    }
    .info-row:last-child {
      margin-bottom: 0;
      border-top: 1px solid rgba(255, 248, 238, 0.1);
      padding-top: 12px;
    }

    .info-label {
      color: rgba(255, 248, 238, 0.6);
    }

    .info-value {
      color: var(--white);
      font-weight: 600;
    }

    .btn-home {
      display: inline-block;
      background: linear-gradient(135deg, var(--saffron) 0%, var(--saffron-deep) 100%);
      color: var(--white);
      text-decoration: none;
      padding: 16px 36px;
      font-size: 18px;
      font-weight: 700;
      border-radius: 50px;
      border: none;
      cursor: pointer;
      box-shadow: 0 10px 20px rgba(224, 123, 42, 0.3);
      transition: all 0.3s ease;
    }

    .btn-home:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 30px rgba(224, 123, 42, 0.5);
    }

    .btn-home:active {
      transform: translateY(-1px);
    }

    /* Responsive */
    @media (max-width: 576px) {
      .thankyou-container {
        padding: 40px 20px;
      }
      h1 {
        font-size: 26px;
      }
      .greeting {
        font-size: 18px;
      }
      .message {
        font-size: 15px;
      }
    }
  </style>
</head>
<body>

<div class="glow-1"></div>
<div class="glow-2"></div>

<main class="thankyou-container">
  <!-- Animated Checkmark -->
  <div class="success-checkmark">
    <div class="check-icon">
      <span class="icon-line line-tip"></span>
      <span class="icon-line line-long"></span>
    </div>
  </div>

  <h1>ऑर्डर सफलतापूर्वक दर्ज हो गया है!</h1>
  <p class="greeting" id="customerGreeting">धन्यवाद!</p>
  
  <div class="message">
    आपका ऑर्डर हमें प्राप्त हो गया है। <strong>OJAS + Capsule</strong> का यह पैकेट आपके दिए गए पते पर भेज दिया जाएगा। हमारी टीम अगले 24 घंटों में कॉल के माध्यम से आपसे संपर्क करके ऑर्डर की पुष्टि (verification) करेगी।
  </div>

  <div class="order-info-box">
    <div class="info-row">
      <span class="info-label">उत्पाद (Product):</span>
      <span class="info-value" style="color: var(--gold);">OJAS + Capsule (1 Pack)</span>
    </div>
    <div class="info-row">
      <span class="info-label">भुगतान प्रकार (Payment):</span>
      <span class="info-value">Cash on Delivery (COD)</span>
    </div>
    <div class="info-row">
      <span class="info-label">कीमत (Price):</span>
      <span class="info-value">₹1,499</span>
    </div>
  </div>

  <a href="index.php" class="btn-home">⬅️ होम पेज पर वापस जाएं</a>
</main>

<script>
  // Dynamic greeting using sessionStorage
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const name = sessionStorage.getItem('ordered_name');
      if (name) {
        document.getElementById('customerGreeting').textContent = `धन्यवाद, ${name}!`;
      }
    } catch (e) {
      console.warn('sessionStorage is blocked or inaccessible:', e);
    }
  });
</script>

</body>
</html>
