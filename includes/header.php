    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Document</title>
      <link rel="stylesheet" href="../style/includes.css">

    </head>

    <body>


      <nav class="navbar">
        <a href="/course-project-itcs333_group36-sec08/index.php">
          <div class="nav-header">PROJECT</div>
        </a>


        <button class="burger-menu" id="burgerBtn">
          <span></span>
          <span></span>
          <span></span>
        </button>

        <ul class="nav-links" id="navLinks">

          <li><a href="/course-project-itcs333_group36-sec08/Admin-Pages/Assignment.php">Assignment</a></li>


        </ul>
      </nav>
      <script>
        const burgerBtn = document.getElementById('burgerBtn');
        const navLinks = document.getElementById('navLinks');

        burgerBtn.addEventListener('click', () => {
          navLinks.classList.toggle('active');
          burgerBtn.classList.toggle('active');
        });

        document.querySelectorAll('.nav-links a').forEach(link => {
          link.addEventListener('click', () => {
            navLinks.classList.remove('active');
            burgerBtn.classList.remove('active');
          });
        });

        document.addEventListener('click', (e) => {
          if (!e.target.closest('.navbar')) {
            navLinks.classList.remove('active');
            burgerBtn.classList.remove('active');
          }
        });
      </script>
    </body>

    </html>