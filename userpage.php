<?php
    session_start();
    include('header.php');
?>

<head>
    <link rel="stylesheet" type="text/css" href="css\ProfileStyle.css">
    <link rel="stylesheet" type="text/css" href="css\BlogStyle.css">
</head>

<body>
    <?php
    require 'includes/dbh.inc.php';
    include 'distance.php';

    $id = $_GET['IdUser'];

    if (isset($_SESSION['userId'])) {
        // Retrieve postal code from current user
        $currentUserId = $_SESSION['userId'];
        $sql = "SELECT postalCode FROM User WHERE idUser = $currentUserId";
        $statement = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($statement, $sql)) {
            header("Location: userpage?error=sqlerror");
            echo '<div class="newposterror"><p>Er is iets fout gegaan (sql error).</p></div>';
        }
        else {
            mysqli_stmt_execute($statement);
            $result = mysqli_stmt_get_result($statement);
            if ($row = mysqli_fetch_assoc($result)) {
                $currentUserPostalCode = $row['postalCode'];
            }
        }
    }
    
    $sql = $conn->query("SELECT * FROM User u WHERE idUser='$id'") or die($conn->error);

    /* fetch associative array */
    while ($row = $sql->fetch_assoc()) {
        $gebruikersnaam = $row["usernameUser"];
        $biography = $row["biography"];
        $_SESSION["idUser"] = $row["idUser"];
    }

    //count total advertisements of user
    $sql = $conn->query("SELECT COUNT(a.idAd) as totalAds FROM User u JOIN Advertisement a ON a.userId = u.idUser WHERE idUser='$id'") or die($conn->error);
    while ($row = $sql->fetch_assoc()) {
        $countAds = $row["totalAds"];
    }

    //count total blogposts of user
    $sql = $conn->query("SELECT COUNT(b.idPost) as totalBlogs FROM User u JOIN Blogpost b ON b.blogUserId = u.idUser WHERE idUser='$id'") or die($conn->error);
    while ($row = $sql->fetch_assoc()) {
        $countBlogs = $row["totalBlogs"];
    }
    ?>
    <table class="profilebox">
        <tr>
            <div class="profilebox">
                <h1><?php echo $gebruikersnaam; ?>'s profiel</h1>
            </div>
        </tr>
        <tr>
            <div class="userprofilebox">
                <div class="userprofile-up-left">
                    <p>Beoordeling</p>
                    <?php
                        $sql = "SELECT * FROM Rating WHERE adUser = '$id'";
                        $result = $conn->query($sql);

                        $allUserRatingRates = array();
                        foreach($result as $ratingId) {
                            // output data of each row
                            array_push($allUserRatingRates, $ratingId['userRating']);
                        }
                        $averageUserRating = array_sum($allUserRatingRates) / count($allUserRatingRates);
                        if(count($allUserRatingRates) == 0){
                            $averageUserRating = 0;
                        }
                        for($i = 0; $i < $averageUserRating; $i++){
                            echo '<label class="fa fa-star star-checked"></label>';
                        }
                        for($i = 4; $i >= $averageUserRating; $i--){
                            echo '<label class="fa fa-star"></label>';
                        }
                        echo "<h3 style='display: inline;'>(".count($allUserRatingRates).")</h3>";
                    ?>
                    <p>Aantal advertenties</p>
                    <p class="userData"><?php echo $countAds; ?> advertenties</p>
                    <p>Aantal blogposts</p>
                    <p class="userData"><?php echo $countBlogs; ?> blogposts</p>
                </div>
                <div class="userprofile-up-right">
                    <p>Gebruikersnaam</p>
                    <p class="userData"><?php echo $gebruikersnaam; ?></p>
                    <p>Biografie</p>
                    <p class="userData"><?php echo $biography != "" ? $biography : "Gebruiker heeft nog geen biografie toegevoegd." ; ?></p>
                </div>
            </div>
        </tr>
    </table>

    <!-- User advertisements and blogposts switching tabs -->
    <div class="ads-blogs-box">
        <div class="ads-blogs-tabs">
            <div id="ads-blogs-btns"></div>
                <button id='ad-btn' class='ads-blogs-toggle-btn' onclick="leftClick()"><?php echo $gebruikersnaam; ?>'s advertenties</button>
                <button id='blog-btn' class='ads-blogs-toggle-btn' onclick="rightClick()"><?php echo $gebruikersnaam; ?>'s blogposts</button>
        </div>
    </div>

    <div id="userAdsList">
        <div class="img-area">
        <?php
            $sql = "SELECT * FROM Advertisement a JOIN User u ON a.userId = u.idUser JOIN AdImage ai ON a.idAd = ai.idAdvert WHERE a.userId = '$id' ORDER BY a.idAd DESC";

            $result = $conn->query($sql);
            //array with all advertisement Ids
            //array used to avoid showing the same advertisement more than once with a different image
            $allIdAdvertisements = array();
        
            if ($result->num_rows > 0) {
                // output data of each row
                while ($row = $result->fetch_assoc()) {
                    //checks if advertisement id already exists in array > if advertisement id exists in array -> skip current advertisement
                    if(!in_array($row['idAd'], $allIdAdvertisements)){
                        if (isset($_SESSION['userId'])) {
                            $distance = getDistance($row['postalCode'], $currentUserPostalCode);
                        } else {
                            $distance = "-- km";
                        }
                        echo '<div class="plant">
                                <a class="linkPlant" href="adinfo?idAd='.$row['idAd'].'">
                                    <div class="adImage">
                                        <img src="uploads/'.$row["imgName"].'" alt="">
                                    </div>
                                    <div class="description">
                                        <h2>'.$row['plantName'].'</h2>
                                        <br>
                                        <h3> Afstand: <span>'.$distance.'</span></h3>
                                        <h3> Datum: <span>'.date("d-m-Y", strtotime($row['postDate'])).'</span></h3>
                                    </div>
                                </a>
                            </div>';
                        //add advertisement id to array
                        array_push($allIdAdvertisements, $row['idAd']);
                    }
                }
            } else {
                echo "<p class='emptyAdOrBlogList'>Gebruiker heeft nog geen advertenties geplaatst.</p>";
            }
        ?>
        </div>
    </div>
    <div id="userBlogsList">
        <div class="grid-3-col">
        <?php
            $sql = "SELECT * FROM Blogpost b JOIN User u ON b.blogUserId = u.idUser LEFT JOIN BlogImage bi ON b.idPost = bi.idBlog WHERE b.blogUserId = '$id' ORDER BY b.idPost DESC";
            $result = $conn->query($sql);
            $number_of_posts = $result->num_rows;
            //array with all blogpost Ids
            $allIdPosts = array();
            if ($result->num_rows > 0) {
                // output data of each row
                while ($row = $result->fetch_assoc()) {
                    //checks if blogpost id already exists in array > if blogpost id exists in array -> skip current blogpost
                    if(!in_array($row['idPost'], $allIdPosts)){
                        if(empty($row["imgName"])){
                            $blogImage = 'images/plantje.png';
                        } else {
                            $blogImage = 'uploads/'.$row['imgName'];
                        }
                        echo '<div class="blogpost">
                                <a class="linkPlant" href="bloginfo?idBlog='.$row["idPost"].'">
                                <div class="blogImage">
                                    <img src="'.$blogImage.'" alt="">
                                </div>
                                <div class="blogDescription">
                                    <h2>'.$row["blogTitle"].'</h2>
                                    <h3>'.$row["firstName"].'</h3>
                                    <p>'.$row["blogDesc"].'</p>
                                    <h4 class="alignleft">'.date_format(date_create($row["blogDate"]),"d-m-Y").'</h4>
                                    <h4 class="alignright">'.$row["blogCategory"].'</h4>
                                </div>
                                </a>
                              </div>';
                        //add blogpost id to array
                        array_push($allIdPosts, $row['idPost']);
                    }
                }
            } else {
                echo "<p class='emptyAdOrBlogList'>Gebruiker heeft nog geen blogposts geplaatst.</p>";
            }
        ?>
        </div>
    </div>
</body>

<?php
    include('footer.php');
    include('feedback.php');
?>