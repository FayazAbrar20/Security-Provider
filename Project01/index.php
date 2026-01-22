<!DOCTYPE html>
<html>
<head>
<title>Security Provider</title>
<link rel="stylesheet" href=".//index.css">
<style>
.navbar {
   display: flex;
   align-items: center;
   justify-content: space-between;
   padding: 10px 40px;
   position: sticky;
   top: 0;
   background: rgba(16, 15, 15, 0.3);
   backdrop-filter: blur(10px);
   z-index: 1000;
   color:white;

}

.btn-nav {
   display: flex;
   gap: 10px;
}

.btn-signin, .btn-register {
   background: rgba(255,255,255,0.2);
   color: white;
   padding: 10px 20px;
   border-radius: 25px;
   text-decoration: none;
}

.signin-dropdown {
      position: absolute;
      top: 25px;
      right: -30;
      background: #2c3e50;
      border-radius: 8px;
      min-width: 120px;
      opacity: 0;
      visibility: hidden;
}

.register-dropdown {
      position: absolute;
      top: 25px;
      right: 0;
      background: #2c3e50;
      border-radius: 8px;
      min-width: 150px;
      opacity: 0;
      visibility: hidden;
}

.btn-signin:hover + .signin-dropdown,
.signin-dropdown:hover {
      opacity: 1;
      visibility: visible;
}

.btn-register:hover + .register-dropdown,
.register-dropdown:hover {
      opacity: 1;
      visibility: visible;
}

.signin-dropdown a, .register-dropdown a {
      display: block;
      padding: 12px 15px;
      color: white;
      font-size: 14px;
}

.signin-dropdown a:hover, .register-dropdown a:hover {
      background: #34495e;
}



body {
   margin: 0;
   min-height: 100vh;
   background-image: url("https://bc-user-uploads.brandcrowd.com/public/media-Production/84cb8f0c-efa9-43e9-9f06-bd99bde7b0f2/27cb0a83-1706-4400-8ec0-a8aa65293eb2.jpg");
   background-size: cover;
   background-position: center;
   background-repeat: no-repeat;
   background-attachment: fixed;
   color:white;
   font-family: 'Claus Eggers Sørensen', sans-serif;

}

.stats {
   display: flex;
   justify-content: space-around;
   align-items: center;
   padding: 20px;
   border-top: 1px solid black;
   border-bottom: 1px solid black;
   width: 100%;
       font-size: 20px;
}
.heading {
   display: flex;
   justify-content: center;
   align-items: center;
   flex-direction: column;
   justify-content: space-between;
   text-align: center;
   margin-top: 200px;
   margin-bottom: 50px;
       font-size: 20px;

}
.links1 {
   display: block;
   justify-content: center;
   align-items: center;
   flex-direction: row;
   margin-bottom: 200px;
   justify-content: space-between;
   text-align: center;
}
a {
   display: inline block;
   margin: 20px;
   color: white;
   text-decoration: none;
   background-color: transparent;
   padding: 20px 20px;
   border-radius: 100px;
}
a:hover {
   color : black;
   background: #f1cc75;
}
.stat {
   text-align: center;
}
.profiles {
   display: flex;
   justify-content: space-around;
   align-items: center;
   padding: 15px;
   margin-top: 100px;
   margin-bottom: 100px;
       font-size: 20px;

}
.profile {
   display: flex;
   flex-direction: column;
   justify-content: space-around;
   align-items: center;
   padding: 15px;
   border: 2px solid #DBDBDB;
}
.profile:hover {
   transform: scale(1.02);
}
.btn {
   color: white;
   background-color: white;
   border: #C7C7C7;
   padding: 10px 10px;
   border-radius: 100px;
   cursor: pointer;
   background-color: transparent;
}
.btn:hover {
   color: black;
   background: #f1cc75;
}
.footer {
   color:black;
   display: flex;
   justify-content: space-around;
   align-items: center;
   padding: 30px;
   border-top: 1px solid black;
   border-bottom: 1px solid black;
   width: 100%;
   margin-bottom: 100px;
   background: #f1cc75;
   font-size: 20px;

}

</style>
</head>
<body>
<nav class="navbar">
<img class="img1" src="uploads/index/Image (12).jpg"
           height="80px" width="80px">
<H1 class = "nav1">SECURITY PROVIDER</H1>

   <div class="btn-nav">
      <a href="#" class="btn-signin">Sign In </a>
      <div class="signin-dropdown">
            <a href="Admin/admin_login.php"> Admin </a>
            <a href="Client/client_login.php"> Client </a>
            <a href="Guard/guard_login.php"> Guard </a>
      </div>
      
      <a href="#" class="btn-register">Register</a>
      <div class="register-dropdown">
            <a href="Client/client_register.php"> Register as Client</a>
            <a href="Guard/guard_register.php"> Register as Guard</a>
      </div>
   </div>
</nav>

<section class="heading">
<label>
<h1>Professional Security <br>On Demand.</h1>
</label>
<label>
<h2>Book top-rated security professionals for your events, business, or personal needs in minutes. Verified,
               trained, and ready to serve.</h2>
</label>
</section>
<section class="links1">
<a href="#profiles">
           Browse Guards </a>
<a href="#footer">
           How it works
</a><br>
</section>
<section class="stats">
<div class="stat">
<h2>20+</h2>
<p>Active Guards</p>
</div>
<div class="stat">
<h2>4.8/5</h2>
<p>Client rating</p>
</div>
<div class="stat">
<h2>8+</h2>
<p>Cities covered</p>
</div>
<div class="stat">
<h2>10k+</h2>
<p>Hours secured</p>
</div>
</section>
<br>
<section class="profiles" id="profiles">
<div class="profile">
<img src="https://img.freepik.com/free-photo/portrait-male-security-guard-with-uniform_23-2150368731.jpg"
               width="300px" height="200px">
<p>james </p><br>
<p>dhaka</p>
<p class="price">$20/hr</p>
<button class="btn" onclick="alert('please sign in')">View Profile</button>
</div>
<div class="profile">
<img src="https://static.vecteezy.com/system/resources/thumbnails/039/892/385/small/ai-generated-a-security-guard-with-his-arms-crossed-on-the-sidewalk-free-photo.jpeg"
               width="300px" height="200px">
<p>james</p>
<p>dhaka</p>
<p class="price">$20/hr</p>
<button class="btn" onclick="alert('please sign in')">View Profile</button>
</div>
<div class="profile">
<img src="https://static.vecteezy.com/system/resources/thumbnails/039/892/247/small/ai-generated-a-security-guard-with-his-arms-crossed-on-the-sidewalk-free-photo.jpeg"
               width="300px" height="200px">
<p>james</p>
<p>dhaka</p>
<p class="price">$20/hr</p>
<button class="btn" onclick="alert('please sign in')">View Profile</button>
</div>
<div class="profile">
<img src="https://img.freepik.com/premium-photo/security-guard-standing-confidently-front-modern-building-arms-crossed-with-light-solid-color-background_1081303-4476.jpg?semt=ais_hybrid&w=740&q=80"
               width="300px" height="200px">
<p>james</p>
<p>dhaka</p>
<p class="price">$20/hr</p>
<button class="btn" onclick="alert('please sign in')">View Profile</button>
</div>
</section><br><br><br>


<footer class="footer" id="footer">

<div>
<img src="uploads/index/Image (13).jpg"
               width="50px" height="50px">
<p>1.Choose a Guard</p>
<p>Browse profiles, filter by specialty to find the perfect match</p>
</div>
<div>
<img src="uploads/index/Image (11).jpg"
               width="50px" height="50px">
<p>2.Book a Time</p>
<p>Select your dates and times directly on their calendar..</p>
</div>
<div class="img1">
<img src="uploads/index/Image (14).jpg"
               width="50px" height="50px" >
<p>3.Stay Secure</p>
<p>Your guard arrives on time, fully briefed and ready to protect.</p>
</div>

</footer>
</body>

</html>