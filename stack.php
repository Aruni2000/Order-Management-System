<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Technologies</title>
    <style>
        /* Reset and general styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Technologies section styling */
        .technologies-section {
            padding: 50px 20px;
            background-color: #f7f7f7;
            text-align: center;
        }
        
        .stack-label {
            color: #3366ff;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .technologies-title {
            font-size: 42px;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 40px;
        }
        
        .tech-icons-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .tech-icon {
            margin: 15px 30px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tech-icon:hover {
            opacity: 1;
        }
        
        .tech-icon img {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body>
    <section class="technologies-section">
        <div class="stack-label">our core</div>
        <h2 class="technologies-title"> Technologies</h2>
        
        <div class="tech-icons-container">
            <!-- Python -->
            <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg" alt="Python">
            </div>
            
            <!-- Terraform -->
            <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/terraform/terraform-original.svg" alt="Terraform">
            </div>
            
            <!-- AWS -->
            <div class="tech-icon">
                <img src="https://w7.pngwing.com/pngs/555/220/png-transparent-aws-hd-logo.png" alt="AWS">
            </div>
            
            <!-- Docker -->
            <!-- <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/docker/docker-original.svg" alt="Docker">
            </div> -->
            
            <!-- React -->
            <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/react/react-original.svg" alt="React">
            </div>
            
            <!-- Node.js -->
            <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" alt="Node.js">
            </div>
            
            <!-- Laravel -->
            <div class="tech-icon">
                <img src="https://logospng.org/download/laravel/logo-laravel-1024.png" alt="Laravel">
            </div>
            
            <!-- Java -->
            <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg" alt="Java">
            </div>
            
            <!-- MySQL -->
            <div class="tech-icon">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mysql/mysql-original.svg" alt="MySQL">
            </div>
        </div>
    </section>
</body>
</html>