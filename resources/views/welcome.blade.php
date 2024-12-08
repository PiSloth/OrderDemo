<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <!-- <link rel="stylesheet" type="text/css" href="./Odoostyle.css" /> -->
    <style>
        .logo {
            width: 120px;
            cursor: pointer;
        }

        .navbar {
            width: 85%;
            margin: auto;
            padding: 35px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar ul li {
            display: inline-block;
            margin: 0 20px;
            list-style: none;
            position: relative;
        }

        .navbar ul li a {
            text-decoration: none;
            text-transform: capitalize;
            color: rgb(198, 37, 203);
        }

        .navbar ul li::after {
            content: "";
            height: 3px;
            width: 0;
            position: absolute;
            background: #662faa;
            left: 0;
            bottom: -10px;
            transition: 0.05s;
        }

        .navbar ul li:hover::after {
            width: 100%;
        }

        .container {
            position: relative;
            background-image: url({{ asset('images/Ring.jpg') }});
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: contain;
            height: 100vh;
            width: 100%;
            background-position: center;
        }

        .content {
            width: 100%;
            position: absolute;
            top: 100%;
            transform: translateY(-50%);
            text-align: center;
        }

        #text {

            background-image: linear-gradient(50deg,
                    rgb(9, 46, 255),
                    rgb(255, 255, 14),
                    rgb(238, 5, 47));
            /* color: #a4a67a;  */
            background-clip: text !important;
            color: transparent;
            box-shadow: 4px 4px 10px rgba(20, 20, 20, 0.3);
            /* padding: 4px; */
            /* text-shadow: 0 0 5px rgb(0, 115, 255), 0 0 10px #0ff, 0 0 15px rgb(221, 255, 0), 0 0 20px rgb(255, 51, 0); */

        }



        @media (min-width: 1024px) {
            #text {
                font-size: 50px;
                /* lg */
            }
        }

        @media (min-width: 1280px) {
            #text {
                font-size: 40px;

            }

            /* xl */
        }

        @media (min-width: 1536px) {
            #text {
                font-size: 40px;

            }

            /* 2xl */
        }

        @media (min-width: 640px) {
            #text {
                font-size: 4rem;
                /* sm size */
            }
        }

        @media (min-width: 768px) {
            #text {
                font-size: 3rem;
                /* lg size */
            }
        }

        .shadowbox {
            position: absolute;
            /* equivalent to `absolute` */
            top: 0;
            /* part of `inset-0` */
            right: 0;
            /* part of `inset-0` */
            bottom: 0;
            /* part of `inset-0` */
            left: 0;
            /* part of `inset-0` */
            background: linear-gradient(to top,
                    #151d29,
                    /* from-gray-900 (dark gray) */
                    rgba(25, 30, 44, 0.4)
                    /* via-gray-900/40 (dark gray with 40% opacity) */
                );
            z-index: -2;
        }

        .log {
            width: 300px;
            padding: 20px 30px;
            text-align: center;
            border-radius: 40px;
            margin: 20px 10px;
            font-weight: bold;
            border: 5px solid #a4a67a;
            background: transparent;
            color: #000000;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            font-size: large;
        }

        span {
            background: #d8a217;
            height: 100%;
            width: 0;
            border-radius: 25px;
            position: absolute;
            left: 0;
            bottom: 0;
            z-index: -1;
            transition: 0.05s;
        }

        .footer {
            font-size: small;
            color: rgb(127, 81, 22);
            margin: 100px;
            text-align: center;
        }

        button:hover span {
            width: 100%;
        }

        button:hover {
            border: none;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <img src="{{ asset('images/logo.png') }}" class="logo" />
        {{-- <ul>
            <li><a href="">Home</a></li>
            <li><a href="">Contact Us</a></li>
            <li><a href="">About Us</a></li>
        </ul> --}}
    </div>
    <div class="container"></div>
    <div class="content">
        <div style="position: relative;">
            <h1 id="text">Let's Order Luxurious Things in a Click</h1>
            <div class="shadowbox"></div>
        </div>
        <p>Hope You Will Like This Order Website Design</p>
        <div>
            <a href="{{ route('login') }}" wire:navigate>
                <button type="button" class="log">
                    Log In
                    <span></span>
                </button>
            </a>
        </div>
    </div>

    <div class="footer">Represented by Su Sandar Lin from IT department</div>
</body>

</html>
