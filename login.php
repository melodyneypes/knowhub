<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- bootsrap css ihuli yung design -->
    <link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body {
            background: url('assets/images/psu.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1; 
        }
        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-header img {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .g_id_signin {
            display: flex;
            justify-content: center;
        }
        .navbar {
            position: relative;
            z-index: 1; 
        }
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(158, 224, 255, 1);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        /* loader style galing sa uiverse */
        .loader {
            --background: linear-gradient(135deg, #23C4F8, #275EFE);
            --shadow: rgba(39, 94, 254, 0.28);
            --text: #6C7486;
            --page: rgba(255, 255, 255, 0.36);
            --page-fold: rgba(255, 255, 255, 0.52);
            --duration: 3s;
            width: 200px;
            height: 140px;
            position: relative;
        }

        .loader:before, .loader:after {
            --r: -6deg;
            content: "";
            position: absolute;
            bottom: 8px;
            width: 120px;
            top: 80%;
            box-shadow: 0 16px 12px var(--shadow);
            transform: rotate(var(--r));
        }

        .loader:before {
            left: 4px;
        }

        .loader:after {
            --r: 6deg;
            right: 4px;
        }

        .loader div {
            width: 100%;
            height: 100%;
            border-radius: 13px;
            position: relative;
            z-index: 1;
            perspective: 600px;
            box-shadow: 0 4px 6px var(--shadow);
            background-image: var(--background);
        }

        .loader div ul {
            margin: 0;
            padding: 0;
            list-style: none;
            position: relative;
        }

        .loader div ul li {
            --r: 180deg;
            --o: 0;
            --c: var(--page);
            position: absolute;
            top: 10px;
            left: 10px;
            transform-origin: 100% 50%;
            color: var(--c);
            opacity: var(--o);
            transform: rotateY(var(--r));
            -webkit-animation: var(--duration) ease infinite;
            animation: var(--duration) ease infinite;
        }

        .loader div ul li:nth-child(2) {
            --c: var(--page-fold);
            -webkit-animation-name: page-2;
            animation-name: page-2;
        }

        .loader div ul li:nth-child(3) {
            --c: var(--page-fold);
            -webkit-animation-name: page-3;
            animation-name: page-3;
        }

        .loader div ul li:nth-child(4) {
            --c: var(--page-fold);
            -webkit-animation-name: page-4;
            animation-name: page-4;
        }

        .loader div ul li:nth-child(5) {
            --c: var(--page-fold);
            -webkit-animation-name: page-5;
            animation-name: page-5;
        }

        .loader div ul li svg {
            width: 90px;
            height: 120px;
            display: block;
        }

        .loader div ul li:first-child {
            --r: 0deg;
            --o: 1;
        }

        .loader div ul li:last-child {
            --o: 1;
        }

        .loader span {
            display: block;
            left: 0;
            right: 0;
            top: 100%;
            margin-top: 20px;
            text-align: center;
            color: var(--text);
            color: #000000ff;
            font-size: 40px;
        }

        @keyframes page-2 {
            0% {
                transform: rotateY(180deg);
                opacity: 0;
            }

            20% {
                opacity: 1;
            }

            35%, 100% {
                opacity: 0;
            }

            50%, 100% {
                transform: rotateY(0deg);
            }
        }

        @keyframes page-3 {
            15% {
                transform: rotateY(180deg);
                opacity: 0;
            }

            35% {
                opacity: 1;
            }

            50%, 100% {
                opacity: 0;
            }

            65%, 100% {
                transform: rotateY(0deg);
            }
        }

        @keyframes page-4 {
            30% {
                transform: rotateY(180deg);
                opacity: 0;
            }

            50% {
                opacity: 1;
            }

            65%, 100% {
                opacity: 0;
            }

            80%, 100% {
                transform: rotateY(0deg);
            }
        }

        @keyframes page-5 {
            45% {
                transform: rotateY(180deg);
                opacity: 0;
            }

            65% {
                opacity: 1;
            }

            80%, 100% {
                opacity: 0;
            }

            95%, 100% {
                transform: rotateY(0deg);
            }
        }
    </style>
</head>
<body>
    <!-- navbar -->
    <nav class="navbar navbar-light shadow-sm bg-light" >
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources for PSU- Alaminos City Campus</a>
        </div>
    </nav>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <img src="assets/images/psu.png" alt="PSU Logo">
                
                <h2 class="text-primary">Login with Google</h2>
                <p class="text-muted">Sign in to access your dashboard</p>
            </div>

            <!-- i-load yung Google Sign-in functionality and kunin yung specific na client id then yung js function -->
            <div id="g_id_onload" 
                data-client_id="207510262129-19qlbi8qqudppurf1juulig88qd74qra.apps.googleusercontent.com" 
                data-callback="handleCredentialResponse">
            </div>
            <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="filled_blue" data-size="la"></div>
           
            <!-- request access-->
            <div class="mt-4 text-center">
                <h5 class="text-secondary">Alumni or Graduate?</h5>
                <p class="text-muted">If you don't have a PSU institutional account, request access here:</p>
                <form action="alumni-requests.php" method="POST" style="max-width: 350px; margin: 0 auto;">
                    <div class="mb-3">
                        <input type="text" name="alumni_name" class="form-control" placeholder="Enter your full name" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="alumni_email" class="form-control" placeholder="Enter your email address" required>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="batch_year" class="form-control" placeholder="Enter your batch year (e.g., 2022)" min="1900" max="2099" required>
                    </div>
                    <button type="submit" class="btn btn-info w-100">Request Access</button>
                </form>
            </div>
        </div>
    </div>

    <!-- loading overlay galing sa uiverse by Nawsome -->
    <div id="loadingOverlay">
        <div class="loader">
            <div>
                <ul>
                    <li>
                        <svg fill="currentColor" viewBox="0 0 90 120">
                            <path d="M90,0 L90,120 L11,120 C4.92486775,120 0,115.075132 0,109 L0,11 C0,4.92486775 4.92486775,0 11,0 L90,0 Z M71.5,81 L18.5,81 C17.1192881,81 16,82.1192881 16,83.5 C16,84.8254834 17.0315359,85.9100387 18.3356243,85.9946823 L18.5,86 L71.5,86 C72.8807119,86 74,84.8807119 74,83.5 C74,82.1745166 72.9684641,81.0899613 71.6643757,81.0053177 L71.5,81 Z M71.5,57 L18.5,57 C17.1192881,57 16,58.1192881 16,59.5 C16,60.8254834 17.0315359,61.9100387 18.3356243,61.9946823 L18.5,62 L71.5,62 C72.8807119,62 74,60.8807119 74,59.5 C74,58.1192881 72.8807119,57 71.5,57 Z M71.5,33 L18.5,33 C17.1192881,33 16,34.1192881 16,35.5 C16,36.8254834 17.0315359,37.9100387 18.3356243,37.9946823 L18.5,38 L71.5,38 C72.8807119,38 74,36.8807119 74,35.5 C74,34.1192881 72.8807119,33 71.5,33 Z"></path>
                        </svg>
                    </li>
                    <li>
                        <svg fill="currentColor" viewBox="0 0 90 120">
                            <path d="M90,0 L90,120 L11,120 C4.92486775,120 0,115.075132 0,109 L0,11 C0,4.92486775 4.92486775,0 11,0 L90,0 Z M71.5,81 L18.5,81 C17.1192881,81 16,82.1192881 16,83.5 C16,84.8254834 17.0315359,85.9100387 18.3356243,85.9946823 L18.5,86 L71.5,86 C72.8807119,86 74,84.8807119 74,83.5 C74,82.1745166 72.9684641,81.0899613 71.6643757,81.0053177 L71.5,81 Z M71.5,57 L18.5,57 C17.1192881,57 16,58.1192881 16,59.5 C16,60.8254834 17.0315359,61.9100387 18.3356243,61.9946823 L18.5,62 L71.5,62 C72.8807119,62 74,60.8807119 74,59.5 C74,58.1192881 72.8807119,57 71.5,57 Z M71.5,33 L18.5,33 C17.1192881,33 16,34.1192881 16,35.5 C16,36.8254834 17.0315359,37.9100387 18.3356243,37.9946823 L18.5,38 L71.5,38 C72.8807119,38 74,36.8807119 74,35.5 C74,34.1192881 72.8807119,33 71.5,33 Z"></path>
                        </svg>
                    </li>
                    <li>
                        <svg fill="currentColor" viewBox="0 0 90 120">
                            <path d="M90,0 L90,120 L11,120 C4.92486775,120 0,115.075132 0,109 L0,11 C0,4.92486775 4.92486775,0 11,0 L90,0 Z M71.5,81 L18.5,81 C17.1192881,81 16,82.1192881 16,83.5 C16,84.8254834 17.0315359,85.9100387 18.3356243,85.9946823 L18.5,86 L71.5,86 C72.8807119,86 74,84.8807119 74,83.5 C74,82.1745166 72.9684641,81.0899613 71.6643757,81.0053177 L71.5,81 Z M71.5,57 L18.5,57 C17.1192881,57 16,58.1192881 16,59.5 C16,60.8254834 17.0315359,61.9100387 18.3356243,61.9946823 L18.5,62 L71.5,62 C72.8807119,62 74,60.8807119 74,59.5 C74,58.1192881 72.8807119,57 71.5,57 Z M71.5,33 L18.5,33 C17.1192881,33 16,34.1192881 16,35.5 C16,36.8254834 17.0315359,37.9100387 18.3356243,37.9946823 L18.5,38 L71.5,38 C72.8807119,38 74,36.8807119 74,35.5 C74,34.1192881 72.8807119,33 71.5,33 Z"></path>
                        </svg>
                    </li>
                    <li>
                        <svg fill="currentColor" viewBox="0 0 90 120">
                            <path d="M90,0 L90,120 L11,120 C4.92486775,120 0,115.075132 0,109 L0,11 C0,4.92486775 4.92486775,0 11,0 L90,0 Z M71.5,81 L18.5,81 C17.1192881,81 16,82.1192881 16,83.5 C16,84.8254834 17.0315359,85.9100387 18.3356243,85.9946823 L18.5,86 L71.5,86 C72.8807119,86 74,84.8807119 74,83.5 C74,82.1745166 72.9684641,81.0899613 71.6643757,81.0053177 L71.5,81 Z M71.5,57 L18.5,57 C17.1192881,57 16,58.1192881 16,59.5 C16,60.8254834 17.0315359,61.9100387 18.3356243,61.9946823 L18.5,62 L71.5,62 C72.8807119,62 74,60.8807119 74,59.5 C74,58.1192881 72.8807119,57 71.5,57 Z M71.5,33 L18.5,33 C17.1192881,33 16,34.1192881 16,35.5 C16,36.8254834 17.0315359,37.9100387 18.3356243,37.9946823 L18.5,38 L71.5,38 C72.8807119,38 74,36.8807119 74,35.5 C74,34.1192881 72.8807119,33 71.5,33 Z"></path>
                        </svg>
                    </li>
                    <li>
                        <svg fill="currentColor" viewBox="0 0 90 120">
                            <path d="M90,0 L90,120 L11,120 C4.92486775,120 0,115.075132 0,109 L0,11 C0,4.92486775 4.92486775,0 11,0 L90,0 Z M71.5,81 L18.5,81 C17.1192881,81 16,82.1192881 16,83.5 C16,84.8254834 17.0315359,85.9100387 18.3356243,85.9946823 L18.5,86 L71.5,86 C72.8807119,86 74,84.8807119 74,83.5 C74,82.1745166 72.9684641,81.0899613 71.6643757,81.0053177 L71.5,81 Z M71.5,57 L18.5,57 C17.1192881,57 16,58.1192881 16,59.5 C16,60.8254834 17.0315359,61.9100387 18.3356243,61.9946823 L18.5,62 L71.5,62 C72.8807119,62 74,60.8807119 74,59.5 C74,58.1192881 72.8807119,57 71.5,57 Z M71.5,33 L18.5,33 C17.1192881,33 16,34.1192881 16,35.5 C16,36.8254834 17.0315359,37.9100387 18.3356243,37.9946823 L18.5,38 L71.5,38 C72.8807119,38 74,36.8807119 74,35.5 C74,34.1192881 72.8807119,33 71.5,33 Z"></path>
                        </svg>
                    </li>
                    <li>
                        <svg fill="currentColor" viewBox="0 0 90 120">
                            <path d="M90,0 L90,120 L11,120 C4.92486775,120 0,115.075132 0,109 L0,11 C0,4.92486775 4.92486775,0 11,0 L90,0 Z M71.5,81 L18.5,81 C17.1192881,81 16,82.1192881 16,83.5 C16,84.8254834 17.0315359,85.9100387 18.3356243,85.9946823 L18.5,86 L71.5,86 C72.8807119,86 74,84.8807119 74,83.5 C74,82.1745166 72.9684641,81.0899613 71.6643757,81.0053177 L71.5,81 Z M71.5,57 L18.5,57 C17.1192881,57 16,58.1192881 16,59.5 C16,60.8254834 17.0315359,61.9100387 18.3356243,61.9946823 L18.5,62 L71.5,62 C72.8807119,62 74,60.8807119 74,59.5 C74,58.1192881 72.8807119,57 71.5,57 Z M71.5,33 L18.5,33 C17.1192881,33 16,34.1192881 16,35.5 C16,36.8254834 17.0315359,37.9100387 18.3356243,37.9946823 L18.5,38 L71.5,38 C72.8807119,38 74,36.8807119 74,35.5 C74,34.1192881 72.8807119,33 71.5,33 Z"></path>
                        </svg>
                    </li>
                </ul>
            </div>
            <span>Loading....</span>
        </div>
    </div>

    <script>
        function handleCredentialResponse(response) {
            document.getElementById('loadingOverlay').style.display = 'flex';
            fetch("callback.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id_token: response.credential })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url; 
                } else {
                    document.getElementById('loadingOverlay').style.display = 'none'; 
                    alert("Login failed: " + data.message);
                }
            });
        }
    </script>
</body>
</html>