<?php $this->load->view('template/head') ?>
<?php   $previous = "javascript:history.go(-1)";
if(isset($_SERVER['HTTP_REFERER'])) {
    $previous = $_SERVER['HTTP_REFERER'];
} ?>
<style type="text/css">
  body {
            background-color: #181818;
            color: #fff;
            font-family: sans-serif;
            margin: 0;
            padding: 0;
        }
  footer{
  text-align: center;
  background-color: white;
  margin-top: 10px;
  border-radius: 10%;
  /*position: absolute;*/
  bottom: 0;
  width: 100%;
  position: fixed;
  z-index: 200000;
}

        .container {
            padding: 16px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .header h2 {
            margin: 0;
        }

        .header .actions {
            display: flex;
            gap: 8px;
        }

        .header .actions button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .address {
            margin-bottom: 16px;
        }

        .address p {
            margin: 0;
        }

        .address .edit-address {
            color: #4CAF50;
            text-decoration: none;
        }

        .product-list {
            margin-bottom: 16px;
        }

        .product-list .product {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #333;
        }

        .product .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product .product-info img {
            width: 48px;
            height: 48px;
        }

        .product .product-info .details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .product .product-info .details h4 {
            margin: 0;
            font-size: 14px;
        }

        .product .product-info .details p {
            margin: 0;
            font-size: 15px;
        }

        .product .price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .product .price span {
            font-size: 14px;
        }

        .product .price .total {
            font-size: 16px;
            font-weight: bold;
        }

        .voucher {
            background-color: #223c77;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .voucher .title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .voucher .title .icon {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .voucher .title .icon img {
            width: 16px;
            height: 16px;
        }

        .voucher .title .icon span {
            font-size: 15px;
        }

        .voucher .info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .voucher .info span {
            font-size: 15px;
        }

        .voucher .info .total {
            font-size: 14px;
            font-weight: bold;
        }

        .payment-method {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #333;
        }

        .payment-method .method {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-method .method img {
            width: 24px;
            height: 24px;
        }

        .payment-method .method span {
            font-size: 15px;
        }

        .summary {
            margin-bottom: 16px;
        }

        .summary .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #333;
        }

        .summary .item .label {
            font-size: 15px;
        }

        .summary .item .value {
            font-size: 14px;
            font-weight: bold;
        }

        .button {
            width: 100%;
            background-color: #223c77;
            border: none;
            color: white;
            padding: 12px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }
  .loading {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
  margin: 10px auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

  .modal {
   margin-top: 100px;
   top: 10px;
   right: 100px;
   bottom: 0;
   left: 0;
   z-index: 10040000;
   overflow: auto;
   overflow-y: auto;
}
.modalbyr {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.7);
}

.modalbyr-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #fefefe;
  padding: 20px;
  border: 1px solid #888;
  border-radius: 5px;
  max-width: 400px;
  text-align: center;
}

.closebyr {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.closebyr:hover,
.closebyr:focus {
  color: black;
}
footer{
  text-align: center;
  background-color: white;
  margin-top: 10px;
  border-radius: 10%;
  /*position: absolute;*/
  bottom: 0;
  width: 100%;
  position: fixed;
  z-index: 200000;
}
.center {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
</style>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<div id="loadingkonek"></div>
<nav style="background-color: #223c77">
  <p style="text-align: center;padding-top: 13px;padding-bottom:13px;color: white;">Payment Now</p>
</nav>
<body>
    <div class="container">
        <div class="voucher">
          <label style="color: white;">Promo</label>
            <div class="title">
                <select class="form-select" aria-label="Disabled select example">
                  <option selected>Open this select menu</option>
                  <option value="1">One</option>
                  <option value="2">Two</option>
                  <option value="3">Three</option>
                </select>
            </div>
        </div>
        <div class="card mb-6" style="border-color: #223c77">
          <div class="card-header" style="background-color: #223c77;color: white;">Credit Card</div>
          <div class="card-body text-primary">
            <form>
              <div class="form-group">
                  <input type="text" class="form-control" id="cardNumber" placeholder="Card Number">
              </div>
              <div class="form-row">
                  <div class="form-group col-6">
                      <input type="text" class="form-control" id="expiry" placeholder="MM/YY">
                  </div>
                  <div class="form-group col-6">
                      <input type="text" class="form-control" id="cvv" placeholder="CVV">
                  </div>
              </div>
              <div class="form-group">
                  <input type="text" class="form-control" id="nameOnCard" placeholder="Name on the Card">
              </div>
          </form>
          </div>
        </div>
        <div class="summary" style="margin-top: 10px;">
            <div class="item">
                <span class="label">Subtotal</span>
                <span class="value">Rp108.500</span>
            </div>
            <div class="item">
                <span class="label">Disc</span>
                <span class="value">Rp0.000</span>
            </div>
            <div class="item">
            </div>
            <div class="voucher">
              <div class="row">
                <div class="col-7"><label style="font-size: 20px;color: white">Total Payment</label></div>
                <div class="col-5"><label style="font-size: 20px;color: white;float: right">Rp108.500</label></div>
              </div>
            </div>
        </div>
    </div>
</body>
<footer>
<div class="container text-center">
  <button type="button" class="btn btn-outline-primary" style="padding-top: 20px;padding-bottom: 20px;padding-left: 50px;padding-right: 50px;" data-bs-toggle="modal" data-bs-target="#exampleModal" id="button">
  Payment
</button>
<!-- <button type="submit" class="btn btn-outline-primary" style="padding-top: 20px;padding-bottom: 20px;padding-left: 50px;padding-right: 50px;" onclick="order()">
  Order
</button> -->
<a href="<?= base_url() ?><?= $log ?>" class="btn btn-outline-danger" style="padding-top: 20px;padding-bottom: 20px;padding-left: 40px;padding-right: 40px;" id="buttonback">Back</a>
</div>
<br>  
</footer>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>

<?php $this->load->view('template/footer') ?>