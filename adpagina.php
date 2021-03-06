<?php
    session_start();
    include('header.php');
    require 'includes/dbh.inc.php';
    include 'distance.php';
?>

<head>
    <title>Advertisements</title>
    <link rel="stylesheet" type="text/css" href="css\style.css">
</head>

<body>    
    <div class="gallery">
        <h1>Alle aanbiedingen</h1>
        <div class="newadknop">
            <a href="newad"><button href="newad" class="newadbutton"><i class="fas fa-plus"></i> Plant plaatsen</button></a>
        </div>
        
        <div class="searchbar-div">
            <div class="searchbar-margin">
                <div class="searchbar-main">
                    <input type="text" id="advertisementSearchbar" class="searchbar-input" name="search-input" onkeyup="sortByDate()" onfocus="this.value=''" placeholder="Zoeken...">
                </div>
            </div>
        </div>
        <div class="filters">
            <div class="search-filters">
                <h2 class="filtertitel">Zoek filters</h2>
                    <div class="plantsoort">
                        <label class="filterlabel">Soort plant</label>
                        <div class="checkboxplantsoort">
                            <label><input type="checkbox" name="check_list[]" class="soort" value="stekje" onchange="filterThenSortAdvertisement(this.value)">Stekje</label><br>
                            <label><input type="checkbox" name="check_list[]" class="soort" value="kiemplant" onchange="filterThenSortAdvertisement(this.value)">Kiemplant</label><br>
                            <label><input type="checkbox" name="check_list[]" class="soort" value="zaad" onchange="filterThenSortAdvertisement(this.value)">Zaad</label><br>
                            <label><input type="checkbox" name="check_list[]" class="soort" value="bol" onchange="filterThenSortAdvertisement(this.value)">Bol</label><br>
                            <label><input type="checkbox" name="check_list[]" class="soort" value="none" onchange="filterThenSortAdvertisement(this.value)">Weet ik niet</label><br>
                        </div>
                    </div>

                    <div class="filterdatefrom">
                        <label class="filterlabel">Datum vanaf</label><br>
                        <input type="date" name="date_from" id="from" onchange="sortByDate()">
                    </div>
                    
                    <div class="filterdateto">
                        <label class="filterlabel">Datum tot en met</label><br>
                        <input type="date" name="date_to" id="to" onchange="sortByDate()">
                    </div>

                    <?php
                        if(isset($_SESSION['userId'])){
                    ?>
                        <div class="filterdistance">
                            <label class="filterlabel">Afstand tot en met</label><br>
                            <table>
                                <tr>
                                    <td>
                                        <input type="range" min="1" step="0.1" class="distanceSlider" id="selectedDistance" onchange="getDistanceThenSortAdvertisement(this.value)">
                                    </td>
                                    <td>
                                        <p id="distanceInput"></p>
                                    </td>
                                </tr>   
                            </table>
                        </div>
                    <?php
                        }
                    ?>
            </div>
        </div>

    </div> 

    <div class="img-area" id="advertisementGallery">
        <?php 
        if (isset($_SESSION['userId'])) {
            // Retrieve postal code from current user
            $currentUserId = $_SESSION['userId'];
            $sql = "SELECT postalCode FROM User WHERE idUser = $currentUserId";
            $statement = mysqli_stmt_init($conn);
            if (!mysqli_stmt_prepare($statement, $sql)) {
                header("Location: adpagina.php?error=sqlerror");
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
        
        $sql = "SELECT * FROM Advertisement a JOIN User u ON a.userId = u.idUser JOIN AdImage ai ON a.idAd = ai.idAdvert WHERE a.AdStatus = '1' ORDER BY a.idAd DESC";
        
        $statement = mysqli_stmt_init($conn);
        //array with all advertisement Ids
        $allIdAdvertisements = array();
        $maxDistance = 0;
        if (!mysqli_stmt_prepare($statement, $sql)) {
            header("Location: adpagina.php?error=sqlerror");
            echo '<div class="newposterror"><p>Er is iets fout gegaan (sql error).</p></div>';
        }
        else {
            mysqli_stmt_execute($statement);
            $result = mysqli_stmt_get_result($statement);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    //checks if advertisement id already exists in array > if advertisement id exists in array -> skip current advertisement
                    if(!in_array($row['idAd'], $allIdAdvertisements)){
                        if (isset($_SESSION['userId'])) {
                            $distance = getDistance($row['postalCode'], $currentUserPostalCode);
                            $getNumberFromDistance = explode(" ", $distance);
                            if($getNumberFromDistance[0] > $maxDistance){
                                $maxDistance = $getNumberFromDistance[0];
                            }
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
                echo "0 resultaten";
            }
        };
        ?>
    </div>
</body>

<script>
    var allCheckedFilters = [];

    document.getElementById('distanceInput').innerHTML = <?php echo $maxDistance; ?>;
    document.getElementById('selectedDistance').value = <?php echo $maxDistance; ?>;
    document.getElementById('selectedDistance').max = <?php echo $maxDistance; ?>;

    function sortByDate(){
        selectedFromDate = document.getElementById('from').value;
        selectedToDate = document.getElementById('to').value;
        searchValue = document.getElementById('advertisementSearchbar').value;
        distanceHTML = document.getElementById('distanceInput');
        //check if user is logged in else no max distance is set
        if(distanceHTML){
            maxDistanceValue = document.getElementById('distanceInput').innerHTML;
        } else {
            maxDistanceValue = 0;
        }

        $.ajax({
            url: "searchAndFilterAdvertisements.php",
            type: 'post',
            data: {fromDate: selectedFromDate, toDate: selectedToDate, filters: allCheckedFilters, searchInput: searchValue, selectedDistance: maxDistanceValue},
            success: function(result)
            {
                //display result after clicking on "delete blogpost"
                document.getElementById("advertisementGallery").innerHTML = result;
            }
        })
    }

    function getDistanceThenSortAdvertisement(selectedDistance){
        document.getElementById('distanceInput').innerHTML = selectedDistance;
        sortByDate();
    }

    //add selected filter options to array then search for the correct advertisements
    function filterThenSortAdvertisement(value){
        filterAdvertisements(value);
        sortByDate();
    }

    function filterAdvertisements(value){
        if(!allCheckedFilters.includes(value)){
            allCheckedFilters.push(value);
        } else {
            for(i = 0; i < allCheckedFilters.length; i++){
                if(allCheckedFilters[i] == value){
                    allCheckedFilters.splice(i, 1);
                    break;
                }
            }
        }
    }
</script>

<?php
    include('footer.php');
    include('feedback.php');
?>