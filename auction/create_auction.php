<?php
// Show form for sellers to create a new auction

require_once 'utilities.php';

// Redirect if user is not logged in or not a seller
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] != 'seller') {
    header('Location: browse.php');
    exit();
}

include_once("header.php");
?>

<div class="container">

<!-- Create auction form -->
<div style="max-width: 800px; margin: 10px auto">
  <h2 class="my-3">Create new auction</h2>
  <div class="card">
    <div class="card-body">
      <!-- Note: This form does not do any dynamic / client-side / 
      JavaScript-based validation of data. It only performs checking after 
      the form has been submitted, and only allows users to try once. You 
      can make this fancier using JavaScript to alert users of invalid data
      before they try to send it, but that kind of functionality should be
      extremely low-priority / only done after all database functions are
      complete. -->
      <form method="post" action="create_auction_result.php" enctype="multipart/form-data">
        <div class="form-group row">
          <label for="auctionTitle" class="col-sm-2 col-form-label text-right">Title of auction</label>
          <div class="col-sm-10">
            <input type="text"
                   class="form-control"
                   id="auctionTitle"
                   name="title"
                   placeholder="e.g. Black mountain bike"
                   required>
            <small id="titleHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> A short description of the item you're selling, which will display in listings.</small>
          </div>
        </div>

        <div class="form-group row">
          <label for="auctionDetails" class="col-sm-2 col-form-label text-right">Details</label>
          <div class="col-sm-10">
            <textarea class="form-control"
                      id="auctionDetails"
                      name="details"
                      rows="4"
                      required></textarea>
            <small id="detailsHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Full details of the listing to help bidders decide if it's what they're looking for.</small>
          </div>
        </div>

        <div class="form-group row">
          <label for="auctionImage" class="col-sm-2 col-form-label text-right">Item Image</label>
          <div class="col-sm-10">
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="auctionImage" name="auction_image" accept=".jpg, .jpeg, .png">
              <label class="custom-file-label" for="auctionImage">Choose file (JPG/PNG)...</label>
            </div>
            <small class="form-text text-muted">Upload a clear image of your item.</small>
          </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('auctionImage');
            if (input) {
                input.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        var nextSibling = e.target.nextElementSibling;
                        nextSibling.innerText = this.files[0].name;
                    }
                });
            }
        });
        </script>

        <div class="form-group row">
          <label for="auctionCategory" class="col-sm-2 col-form-label text-right">Category</label>
          <div class="col-sm-10">
            <select class="form-control"
                    id="auctionCategory"
                    name="category"
                    required>
              <?php ?>
              <option value="" selected>Choose...</option>
              <?php
              $conn = get_db();
              if ($conn) {
                  $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
                  $result = $conn->query($sql);
                  if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                          echo "<option value='" . htmlspecialchars($row['category_id']) . "'>"
                             . htmlspecialchars($row['category_name']) . "</option>";
                      }
                  } else {
                      echo "<option value='' disabled>NaN</option>";
                  }
              }
              ?>
            </select>
            <small id="categoryHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Select a category for this item.</small>
          </div>
        </div>

        <div class="form-group row">
          <label for="auctionStartPrice" class="col-sm-2 col-form-label text-right">Starting price</label>
          <div class="col-sm-10">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number"
                     class="form-control"
                     id="auctionStartPrice"
                     name="start_price"
                     min="0"
                     step="0.01"
                     required>
            </div>
            <small id="startBidHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Initial bid amount.</small>
          </div>
        </div>

        <div class="form-group row">
          <label for="auctionReservePrice" class="col-sm-2 col-form-label text-right">Reserve price</label>
          <div class="col-sm-10">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number"
                     class="form-control"
                     id="auctionReservePrice"
                     name="reserve_price"
                     min="0"
                     step="0.01">
            </div>
            <small id="reservePriceHelp" class="form-text text-muted">Optional. Auctions that end below this price will not go through. This value is not displayed in the auction listing.</small>
          </div>
        </div>

        <div class="form-group row">
          <label for="auctionStartDate" class="col-sm-2 col-form-label text-right">Start date</label>
          <div class="col-sm-10">
            <input type="datetime-local"
                   class="form-control"
                   id="auctionStartDate"
                   name="start_date">
            <small id="startDateHelp" class="form-text text-muted">Optional. When the auction should start. Leave empty to start immediately.</small>
          </div>
        </div>

        <div class="form-group row">
          <label for="auctionEndDate" class="col-sm-2 col-form-label text-right">End date</label>
          <div class="col-sm-10">
            <input type="datetime-local"
                   class="form-control"
                   id="auctionEndDate"
                   name="end_date"
                   required>
            <small id="endDateHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Day for the auction to end.</small>
          </div>
        </div>

        <button type="submit" class="btn btn-primary form-control">Create Auction</button>
      </form>
    </div>
  </div>
</div>

</div>

<?php include_once("footer.php")?>

