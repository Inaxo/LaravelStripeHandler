<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        clifford: '#da373d',
                    }
                }
            }
        }
    </script>
    <title>Payment Completed Successfully</title>
</head>
<body>
<div class="container-fluid flex-col justify-center items-center w-full text-center">
    <box-icon type='solid' name='check-circle' size="250px" color="green"></box-icon>
    <h2 id="payment-message" style="font-family: 'Poppins'">Payment completed successfully. Please wait to be redirected to the main page</h2>
</div>

<script>
    let paymentMessage = document.getElementById('payment-message');

    for (let i = 0; i < 8; i++) {
        setTimeout(() => {
            if (paymentMessage.textContent.length > 79) {
                paymentMessage.textContent = 'Payment completed successfully. Please wait to be redirected to the main page';
            }
            paymentMessage.textContent += '.';
        }, 1000 + (i * 1000));
    }
    setTimeout(() => {
        window.location.href = '/';
    }, 8000);
</script>
</body>
</html>
