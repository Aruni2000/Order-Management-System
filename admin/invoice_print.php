<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FE IT Solutions - Invoice Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            position: relative;
        }

        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 0.25rem;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }

        .message-cell {
            max-width: 200px;
            position: relative;
        }

        .message-content {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .spinner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        body {
            background-color: #f4f6f9;
            /* Light grey background */
            color: #333;
            /* Dark text color for readability */
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            position: relative;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
            background: white;
        }

        .table th {
            background: linear-gradient(to right, #4CAF50, #17a2b8);
            /* Green to blue gradient */
            color: white;
            text-align: left;
            padding: 10px;
        }

        .table thead {
            background: linear-gradient(to right, #4CAF50, #17a2b8);
            /* Green to blue gradient */
        }

        .table thead th {
            color: white;
            text-align: left;
            padding: 10px;
            border: none;
            /* Remove individual column borders */
            background: none;
            /* Ensure no override from individual th */
        }


        .table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
            /* Light grey for alternating rows */
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef;
            /* Highlight on hover */
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.875rem;
            border-radius: 4px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        /* Sidebar Customization */
        .sb-sidenav {
            background-color: #343a40;
            /* Dark grey sidebar */
            color: white;
        }

        .sb-sidenav .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }

        .sb-sidenav .nav-link:hover {
            color: white;
        }

        .sb-topnav {
            background-color: #343a40;
            /* Dark navbar */
        }

        @media print {
    * {
        -webkit-print-color-adjust: exact !important; /* Chrome, Safari */
        print-color-adjust: exact !important; /* Modern browsers */
        color-adjust: exact !important; /* Legacy support */
    }

    .table thead tr {
        background: linear-gradient(to right, #4CAF50, #17a2b8) !important;
        color: white !important;
    }

    .btn {
        background-color: inherit !important; /* Ensures buttons are visible */
        color: black !important;
        border: 1px solid #000 !important;
    }

    .d-md-flex {
    display: flex !important;
  }
}

    </style>
</head>
<body>
    <div class="row">
    <div class="col-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card">
                                <div class="card-header d-md-flex d-block">
                                    <div class="h5 mb-0 d-sm-flex d-bllock align-items-center">
                                        <div class="avatar avatar-sm">
                                            <img src="img/system/fe_it_logo.png" alt="" style="width: 200px;">
                                        </div>
                                    </div>
                                    <div class="ms-auto mt-md-0 mt-2">
                                    <div class="avatar avatar-sm">
                                            <div class="h6 fw-semibold mb-0">INVOICE : <span class="text-primary"># 1001</span></div>
                                        </div>

                                        <div class="avatar avatar-sm">
                                            <p class="fw-semibold text-muted mb-1">Date Issued :</p>
                                            <p class="fs-15 mb-1"><?php echo date('Y-m-d');?> - <span class="text-muted fs-12"><?php echo date('H:i:s');?></span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gy-3">
                                        <div class="col-xl-12">
                                            <div class="row">
                                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                                    <p class="text-muted mb-2">
                                                        Billing From :
                                                    </p>
                                                    <p class="fw-bold mb-1">
                                                        FE IT Solustions pvt (Ltd)
                                                    </p>
                                                    <p class="mb-1 text-muted">
                                                        No: 04
                                                    </p>
                                                    <p class="mb-1 text-muted">
                                                        Wijayamangalarama Road,Kohuwala
                                                    </p>
                                                    <p class="mb-1 text-muted">
                                                        info@feitsolutions.com
                                                    </p>
                                                    <p class="mb-1 text-muted">
                                                        011-2824524
                                                    </p>
                                                </div>
                                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 ms-auto mt-sm-0 mt-3">
                                                    <p class="text-muted mb-2">
                                                        Billing To :
                                                    </p>
                                                    <p class="fw-bold mb-1">
                                                        Test Name
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        Lane 1
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        city
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        test@gmail.com
                                                    </p>
                                                    <p class="text-muted">
                                                        11111xxxxxxxxxxxx
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <div class="table-responsive">
                                                <table class="table nowrap text-nowrap border mt-4">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>PRODUCT</th>
                                                            <th>DESCRIPTION</th>
                                                            <th style="text-align: right;">TOTAL</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>1</td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                   Pro 1
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-muted">
                                                                    desc1
                                                                </div>
                                                            </td>
                                                            <td style="text-align: right;">
                                                               20,000
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>2</td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                   pro 2
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-muted">
                                                                  desc2
                                                                </div>
                                                            </td>
                                                            <td style="text-align: right;">
                                                                30,000
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>3</td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    pro3
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-muted">
                                                                   descp3
                                                                </div>
                                                            </td>
                                                            <td style="text-align: right;">
                                                                35,000
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>4</td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    pro4
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-muted">
                                                                   descp4
                                                                </div>
                                                            </td>
                                                            <td style="text-align: right;">
                                                                15,000
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2"></td>
                                                            <td colspan="2">
                                                                <table class="table table-sm text-nowrap mb-0 table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td scope="row">
                                                                                <p class="mb-0">Sub Total :</p>
                                                                            </td>
                                                                            <td style="text-align: right;">
                                                                                <p class="mb-0 fw-semibold fs-15">100,000</p>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td scope="row">
                                                                                <p class="mb-0">Discount :</p>
                                                                            </td>
                                                                            <td style="text-align: right;">
                                                                                <p class="mb-0 fw-semibold fs-15">50,000</p>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td scope="row">
                                                                                <p class="mb-0 fs-14">Total :</p>
                                                                            </td>
                                                                            <td style="text-align: right;">
                                                                                <p class="mb-0 fw-semibold fs-16 text-success">50,000</p>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <div>
                                                <label for="invoice-note" class="form-label">Note:</label>
                                                <textarea class="form-control form-control-light" id="invoice-note" rows="3">Once the invoice has been verified by the accounts payable team and recorded, the only task left is to send it for approval before releasing the payment</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>