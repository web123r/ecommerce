<?php
include ("database.php");
include ("functions.php");



if(isset($_POST["submit"]) && isset($_FILES["image"]) && isset($_POST["name"]) && isset($_POST["description"]) && isset($_POST["type"]) && isset($_POST["price"])){
    addItems($conn);
};






?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Material Design for Bootstrap</title>
    <!-- MDB icon -->
    <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
     <link
      rel="stylesheet"
      href="style.css"
    />
    <!-- Google Fonts Roboto -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
    />
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
    <style>

.range-container {
    width: 300px;
    font-family: Arial, sans-serif;
}

.range-container label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
}

.range-row {
    display: flex;
    align-items: center;
    gap: 15px;
}

.range-row input[type="range"] {
    width: 100%;
    cursor: pointer;
}

#quantityValue {
    min-width: 25px;
    font-size: 18px;
    font-weight: bold;
}


/* Slider styling */

.range-row input[type="range"] {
    appearance: none;
    height: 6px;
    border-radius: 10px;
    background: #ddd;
}

.range-row input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #0d6efd;
    cursor: pointer;
}

.range-row input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #0d6efd;
    cursor: pointer;
    border: none;
}

</style>
  </head>
  <body>
    <!-- Start your project here-->
    <div class="container">
      <div class="d-flex justify-content-center align-items-center" style="height: 100vh">
        <div class="text-center">
         <form style="width: 26rem;" enctype="multipart/form-data" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
  <!-- Name input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="name" class="form-control" name="name" />
    <label class="form-label" for="name">Name</label>
  </div>

  <!-- Email input -->
 
  <!-- Message input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <textarea class="form-control" id="description" rows="4" name="description"></textarea>
    <label class="form-label" for="form4Example3">Description</label>
  </div>

   <div data-mdb-input-init class="form-outline mb-4">
    <input type="number" id="price" class="form-control" name="price" />
    <label class="form-label" for="price">Price in Dollars</label>
  </div>



  <!-- Checkbox -->
  <div class="form-check d-flex justify-content-center mb-4">
     <label class="form-check-label" for="type">
      Fruit Category 
    </label>
    
   <select name="type" id="type">
    <option value="fruit">Fruits</option>
    <option value="vegetable">Vegetables</option>
   </select>
   
  </div>

  <div class="range-container">

    <label for="quantity">Rating:</label>

    <div class="range-row">

        <input
            type="range"
            id="quantity"
            min="1"
            max="5"
            value="1"
            name = "rating"
        >

        <span id="quantityValue">1</span>

    </div>

</div>



   <div data-mdb-input-init class="form-outline mb-4">
    <input type="file" id="image" class="form-control" name="image" placeholder="Upload image"/>
    <label class="form-label" for="image">Browse image </label>
  </div>

  <!-- Submit button -->
  <button data-mdb-ripple-init type="submit" name="submit" class="btn btn-primary btn-block mb-4">Add</button>
</form>
          
        </div>
      </div>
    </div>
    <!-- End your project here-->

    <!-- MDB -->
    <script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
    <script>

const slider = document.getElementById("quantity");
const value = document.getElementById("quantityValue");

slider.addEventListener("input", function () {

    value.textContent = slider.value;

});

</script>
  </body>
</html>
