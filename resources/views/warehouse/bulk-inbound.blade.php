@extends('layouts.app')

@section('content')
    <div>
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-2">Bulk Inbound - Sync Existing Warehouse Stock</h2>
                <p class="text-muted">Add multiple items to the system to synchronize existing warehouse inventory.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white">Add Items in Bulk</h5>
                    </div>
                    <div class="card-body">
                        <form id="bulkInboundForm">
                            @csrf

                            <div id="itemsContainer"></div>

                            <button type="button" class="btn btn-secondary mb-3" id="addItemBtn">
                                <i class="fas fa-plus"></i> Add Item Row
                            </button>

                            <hr>

                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Submit Bulk Inbound
                            </button>
                            <a href="{{ route('warehouse.inbound') }}" class="btn btn-secondary btn-lg">
                                Cancel
                            </a>
                        </form>
                    </div>
                </div>

                <div id="responseAlert" class="mt-3"></div>
            </div>
        </div>
    </div>

    <style>
        .item-row {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .item-row:hover {
            background: #f5f5f5;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-number {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }

        .remove-btn {
            cursor: pointer;
            color: red;
        }

        .location-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }

        .location-info.show {
            display: block;
        }

        .progress-bar-striped {
            animation: progress-bar-stripes 1s linear infinite;
        }
    </style>

    <script type="text/javascript">
        var productsData = @json($products);
        let itemCount = 0;

        // Add initial empty row
        function addItemRow() {
            itemCount++;
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.id = 'item-' + itemCount;

            let productOptions = '';
            productsData.forEach(p => {
                productOptions += '<option value="' + p.id + '">' + p.name + ' (' + p.sku + ')</option>';
            });

            let levelOptions = '';
            for (let c of 'ABCDEFGHIJKL') {
                levelOptions += '<option value="' + c + '">' + c + '</option>';
            }

            itemRow.innerHTML = '<div class="item-header"><span class="item-number">Item #' + itemCount +
                '</span><span class="remove-btn" onclick="removeItem(' + itemCount +
                ')"><i class="fas fa-trash"></i> Remove</span></div><div class="row"><div class="col-md-4"><label class="form-label">Product</label><select class="form-control product-select" data-item-id="' +
                itemCount + '" onchange="loadBatches(' + itemCount + ')"><option value="">-- Select Product --</option>' +
                productOptions +
                '</select></div><div class="col-md-4"><label class="form-label">Batch</label><select class="form-control batch-select" data-item-id="' +
                itemCount +
                '"><option value="">-- Select Batch --</option></select></div><div class="col-md-4"><label class="form-label">Quantity</label><input type="number" class="form-control quantity-input" data-item-id="' +
                itemCount + '" min="1" max="50" value="1" onchange="updateAvailableLocations(' + itemCount +
                ')"></div></div><div class="row mt-3"><div class="col-md-4"><label class="form-label">Level</label><select class="form-control level-select" data-item-id="' +
                itemCount + '" onchange="updateHeights(' + itemCount + ')"><option value="">-- Select Level --</option>' +
                levelOptions +
                '</select></div><div class="col-md-4"><label class="form-label">Height</label><select class="form-control height-select" data-item-id="' +
                itemCount +
                '"><option value="">-- Select Height --</option></select></div><div class="col-md-4"><label class="form-label">&nbsp;</label><button type="button" class="btn btn-info w-100" onclick="showAvailableLocations(' +
                itemCount +
                ')"><i class="fas fa-location-arrow"></i> Find Locations</button></div></div><div class="location-info" id="location-info-' +
                itemCount + '"><strong>Available Locations:</strong><div id="location-list-' + itemCount +
                '" style="margin-top: 10px;"></div></div>';

            document.getElementById('itemsContainer').appendChild(itemRow);
        }

        function removeItem(itemId) {
            const row = document.getElementById('item-' + itemId);
            if (row) {
                row.remove();
            }
        }

        function loadBatches(itemId) {
            const productSelect = document.querySelector('.product-select[data-item-id="' + itemId + '"]');
            const batchSelect = document.querySelector('.batch-select[data-item-id="' + itemId + '"]');
            const productId = productSelect.value;

            batchSelect.innerHTML = '<option value="">-- Select Batch --</option>';

            if (!productId) return;

            fetch('/warehouse/batches/' + productId)
                .then(r => r.json())
                .then(batches => {
                    batches.forEach(batch => {
                        const option = document.createElement('option');
                        option.value = batch.id;
                        option.textContent = 'Batch ' + batch.batch_number;
                        batchSelect.appendChild(option);
                    });
                });
        }

        function updateHeights(itemId) {
            const levelSelect = document.querySelector('.level-select[data-item-id="' + itemId + '"]');
            const heightSelect = document.querySelector('.height-select[data-item-id="' + itemId + '"]');

            heightSelect.innerHTML = '<option value="">-- Select Height --</option>';

            if (!levelSelect.value) return;

            for (let i = 1; i <= 6; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = 'Height ' + i;
                heightSelect.appendChild(option);
            }
        }

        function showAvailableLocations(itemId) {
            const productSelect = document.querySelector('.product-select[data-item-id="' + itemId + '"]');
            const levelSelect = document.querySelector('.level-select[data-item-id="' + itemId + '"]');
            const heightSelect = document.querySelector('.height-select[data-item-id="' + itemId + '"]');
            const quantityInput = document.querySelector('.quantity-input[data-item-id="' + itemId + '"]');

            const productId = productSelect.value;
            const level = levelSelect.value;
            const height = heightSelect.value;
            const quantity = quantityInput.value;

            if (!productId || !level || !height) {
                alert('Please select Product, Level, and Height first');
                return;
            }

            fetch('/warehouse/bulk-inbound/locations?product_id=' + productId)
                .then(r => r.json())
                .then(locations => {
                    const locationInfo = document.getElementById('location-info-' + itemId);
                    const locationList = document.getElementById('location-list-' + itemId);

                    const selectedColumn = locations.find(loc => loc.level === level && loc.height == height);

                    if (!selectedColumn) {
                        locationList.innerHTML =
                            '<div class="alert alert-warning">No location found for this selection</div>';
                    } else {
                        locationList.innerHTML = '<div class="alert alert-info"><strong>' + level + height +
                            ':</strong> ' + selectedColumn.available + ' slots available<br><small>Type: ' +
                            selectedColumn.type + '</small></div>';
                    }

                    locationInfo.classList.add('show');
                });
        }

        function updateAvailableLocations(itemId) {
            const locationInfo = document.getElementById('location-info-' + itemId);
            if (locationInfo.classList.contains('show')) {
                locationInfo.classList.remove('show');
            }
        }

        document.getElementById('addItemBtn').addEventListener('click', addItemRow);

        document.getElementById('bulkInboundForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const items = [];
            document.querySelectorAll('.item-row').forEach(row => {
                const itemId = row.id.replace('item-', '');
                const productId = row.querySelector('.product-select').value;
                const batchId = row.querySelector('.batch-select').value;
                const level = row.querySelector('.level-select').value;
                const height = row.querySelector('.height-select').value;
                const quantity = parseInt(row.querySelector('.quantity-input').value);

                if (productId && batchId && level && height && quantity > 0) {
                    items.push({
                        product_id: productId,
                        batch_id: batchId,
                        level: level,
                        height: height,
                        quantity: quantity
                    });
                }
            });

            if (items.length === 0) {
                alert('Please fill at least one complete item row');
                return;
            }

            const btn = document.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                const response = await fetch('/warehouse/bulk-inbound/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        items
                    })
                });

                const result = await response.json();
                const alertDiv = document.getElementById('responseAlert');

                if (result.success) {
                    alertDiv.innerHTML =
                        '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Success!</strong> ' +
                        result.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    document.getElementById('bulkInboundForm').reset();
                    document.getElementById('itemsContainer').innerHTML = '';
                    setTimeout(addItemRow, 500);
                } else {
                    const errorHtml = result.errors.length > 0 ?
                        result.errors.map(e => '<li>' + e + '</li>').join('') :
                        'Unknown error occurred';

                    alertDiv.innerHTML =
                        '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Partial Success:</strong> ' +
                        result.message + '<ul>' + errorHtml +
                        '</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
            } catch (error) {
                document.getElementById('responseAlert').innerHTML =
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error:</strong> ' +
                    error.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Submit Bulk Inbound';
            }
        });

        // Add first item row on load
        window.addEventListener('load', addItemRow);
    </script>
@endsection
