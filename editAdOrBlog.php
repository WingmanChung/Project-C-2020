<?php
    session_start();
    include('header.php');
?>

<head>
    <title>Edit blogpost form</title>
    <link rel="stylesheet" type="text/css" href="css\NewPostStyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.3/tiny-slider.css">
</head>

<body>
    <?php
    // Update blogpost after clicking the submit button
    if(isset($_POST['blog-update'])){
        require 'includes/dbh.inc.php';

        $allowed = ['png', 'jpg', 'gif', 'jpeg', ''];
        $fileCount = count($_FILES['file']['name']);
        //Check if user uploaded images
        for($i=0; $i < $fileCount; $i++){
            if(!in_array(strtolower(pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION)), $allowed)){
                $imageFormats = false;
                break;
            }
            $imageFormats = true;
        }

        if($imageFormats){
            $blogtitle = $_POST["bname"];
            $blogcategory = $_POST["bcategory"];
            //nl2br() function saves breaklines of user
            $blogdescription = nl2br($_POST["bdesc"]);
            $blogLink = $_POST['bLink'];
            $blogId = $_GET['blogpostId'];

            //update blogpost data
            $sql = "UPDATE Blogpost SET blogTitle=?, blogCategory=?, blogDesc=?, blogLink=? WHERE idPost='$blogId'";
            $statement = mysqli_stmt_init($conn);
            if (!mysqli_stmt_prepare($statement, $sql)) {
                echo '<div class="newposterror"><p>Er is iets fout gegaan (sql error).</p></div>';
            }                       
            else {
                mysqli_stmt_bind_param($statement, "ssss", $blogtitle, $blogcategory, $blogdescription, $blogLink);
                mysqli_stmt_execute($statement); 
            }

            //inserts image in uploads folder
            for($i=0; $i < $fileCount; $i++){
                //checks if there is a file uploaded
                if(UPLOAD_ERR_OK == $_FILES['file']['error'][$i]){
                    //give filename a time to avoid overwriting a file (unique name)
                    $fileName = time().$_FILES['file']['name'][$i];

                    //insert image to database
                    $sql = "INSERT INTO BlogImage(imgName, idBlog) VALUES (?, ?)";
                    $statement = mysqli_stmt_init($conn);
                    if (!mysqli_stmt_prepare($statement, $sql)) {
                        echo '<div class="newposterror"><p>Er is iets fout gegaan (sql error).</p></div>';
                    }
                    else {
                        mysqli_stmt_bind_param($statement, "ss", pathinfo($fileName, PATHINFO_BASENAME), $blogId);
                        mysqli_stmt_execute($statement); 
                        
                        //insert image to uploads folder
                        move_uploaded_file($_FILES['file']['tmp_name'][$i], 'uploads/'.$fileName);
                    }
                }
            }
            echo "<script type='text/javascript'>window.top.location='https://www.roy-van-der-lee.nl/fleurtop/editAdOrBlog.php?blogpostId=$blogId&upload=success';</script>";
            echo '<div class="newposterror"><p>Uw blogpost is succesvol geupload!</p></div>';
        } else {
            echo '<div class="newposterror"><p>Alleen "jpg", "png", "gif" en "jpeg" bestanden zijn toegestaan!</p></div>';
        }
    }

    //check if user wants to update a blogpost or advertisement
    if(isset($_GET['blogpostId'])){
        //check if user is logged in
        if (!isset($_SESSION['userId'])){
            echo'<div class="notloggedin">
                    <h4>Om een blogpost te kunnen wijzigen moet u eerst ingelogd zijn. Klik <a href="loginpagina">HIER</a> om in te loggen.</h4>
                </div>';
        }
        //check if registered user is the owner of the blogpost
        else if($_SESSION['userId'] == $_SESSION['idUser']){
            
            // ---------------------------- UPDATE BLOGPOST FORM ---------------------------- 

            $blogId = $_GET['blogpostId'];
            $sql = "SELECT * FROM Blogpost b JOIN User u ON b.blogUserId = u.idUser LEFT JOIN BlogImage bi ON b.idPost = bi.idBlog WHERE b.idPost = '$blogId'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // output data of each row
                $row = $result->fetch_assoc();

                $blogId = $row["idPost"];
                $blogTitle = $row["blogTitle"];
                $blogCategory = $row["blogCategory"];
                //replace <br> tags from blog description with newlines
                $blogDesc = str_replace("<br />", "\n", $row["blogDesc"]);
                $blogLink = $row['blogLink'];
                $blogImages = $_SESSION['blogImages'];
                $blogImages = array();

                $resultInner = $conn->query($sql);
                while ($row2 = mysqli_fetch_array($resultInner)) {
                    array_push($blogImages, $row2["imgName"]);
                }
            }
        ?>
            <div class="blogpostform">
                <h2>Update blogpost</h2><br>
                <form action="" method="post" enctype="multipart/form-data">
                    <label>Blogtitel</label><br>
                    <input type="text" id="bname" name="bname" value="<?php echo $blogTitle; ?>" required><br><br>
                    
                    <label>Blogcategorie</label><br>
                    <select name="bcategory" id="bcategory">
                        <!-- Set value for blogpost category select menu -->
                        <option value="<?php echo $blogCategory; ?>" style="display:none;"><?php echo $blogCategory; ?></option>
                        <option value="verzorging">Verzorging</option>
                        <option value="speciale evenementen">Speciale evenementen</option>
                        <option value="vieringen en feestdagen">Vieringen en feestdagen</option>
                    </select><br><br>

                    <label>Beschrijving</label><br>
                    <textarea id="bdesc" name="bdesc" required><?php echo $blogDesc; ?></textarea><br><br>

                    <label>Afbeeldingen</label><br>
                    <!-- checks if blogpost has images -->
                    <?php
                        if(!empty($blogImages) && $blogImages[0] != ""){
                    ?>
                    <div class="slidertns" id="blogpostImagesSlider">
                        <?php 
                        $idNumber = 0;
                        foreach($blogImages as $blogImage){
                            echo '<img id="blogImage'.$idNumber.'" src="uploads/'.$blogImage.'" alt="">';
                            $idNumber++;
                        }
                        ?>
                    </div>
                    <!-- add delete button for every blogpost image -->
                    <div class="blogpostImages"> 
                        <table class="ads-blogs-list"><tr class="ads-blogs-columnnames"><td><p>Afbeeldingnaam</p></td><td class="blogpostImagesOptions"><p>Opties</p></td></tr>
                            <?php
                                for($i=0, $j=1; $i < count($blogImages); $j++, $i++){
                                    echo '<tr id="imgButton'.$i.'"><td><p><span>Afbeeldingnaam '.$j.': </span>'.$blogImages[$i].'</p></td><td class="blogpostImagesOptions"><button type="button" id="blogpost" onclick="deleteBlogpostImage(this.id, this.value, '.$i.')" value="'.$blogImages[$i].'" class="deleteBlogpostImage">Verwijder</button></td></tr>';
                                }
                            ?>
                        </table>
                    </div>
                    <?php
                        }
                    ?>
                    <input type='file' name='file[]' id='file' multiple><br><br>

                    <label>URL toevoegen</label><br>
                    <input type="url" name="bLink" id="bLink" value="<?php echo $blogLink; ?>"><br><br>
                    <input class="newPostButton" type="submit" name="blog-update" value="Blogpost updaten">
                </form>
            </div>
        <?php
        //show message when registered user tries to edit a blogpost of another user
        } else {
            echo'<div class="notloggedin">
                    <h4>U kunt niet de blogpost van een andere gebruiker wijzigen.</h4>
                 </div>';
        }
    }
    ?>
</body>

<script scr="main.js">
    function deleteBlogpostImage(editpage, imgname, row){
        //delete specific image
        $.ajax({
            url: "includes/editAdOrBlog.inc.php",
            type: 'post',
            data: {function: editpage, blogpostImage: imgname},
            success: function(result)
            {
                //checks which list needs to be updated
                if(result == "success"){
                    if(editpage == "blogpost"){ //admin blogpost list
                        //id of image "delete" button
                        idOfButton = "imgButton" + row;
                        //remove image "delete" button
                        document.getElementById(idOfButton).remove();
                        location.reload(); //refresh page after deleting image
                    }
                }
            }
        })
    }
</script>

<?php
    include('footer.php');
    include('feedback.php');
?>