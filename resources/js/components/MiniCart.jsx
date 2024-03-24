import React, { Component } from "react";
import ReactDOM from "react-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { sum } from "lodash";
class MiniCart extends Component {
    constructor(props) {
        super(props);
        this.state = {
            cart: [],
            products: [],
            customers: [],
            barcode: "",
            search: "",
            customer_id: 1,
            categories: [],
            selectedCategory: null,
            isLoading: false,
            orderId: null,
            changeAmount: null,
            receivedAmount: null,
            isInputFocused: false,
            orderItemId: document.getElementById('minicart').getAttribute('data-orderitemid'),
        };

        this.loadCart = this.loadCart.bind(this);
        this.handleOnChangeBarcode = this.handleOnChangeBarcode.bind(this);
        this.handleScanBarcode = this.handleScanBarcode.bind(this);
        this.handleChangeQty = this.handleChangeQty.bind(this);
        this.handleEmptyCart = this.handleEmptyCart.bind(this);

        this.loadProducts = this.loadProducts.bind(this);
        this.loadCategories = this.loadCategories.bind(this);
        this.handleChangeSearch = this.handleChangeSearch.bind(this);
        this.handleSeach = this.handleSeach.bind(this);
        this.handleCloseDay = this.handleCloseDay.bind(this);
        this.handleCategorySelect = this.handleCategorySelect.bind(this);
        this.setCustomerId = this.setCustomerId.bind(this);
        this.handleClickSubmit = this.handleClickSubmit.bind(this);
        this.barcodeInputRef = React.createRef();

        window.addEventListener('keydown', this.handleKeyPress);
    }

    componentDidMount() {
      // Focus on the input element when the component mounts
      this.barcodeInputRef.current.focus();

        // load user cart
        this.loadCart();
        this.loadProducts();
        this.loadCustomers();
        this.loadCategories();
    }
    loadCustomers() {
        axios.get(`/admin/customers`).then((res) => {
            const customers = res.data;
            this.setState({ customers });
        });
    }


    handleCloseDay() {
        // Implement the logic to close the day here
        // This could include taking a snapshot, generating reports, etc.
        // Once the day is closed, you can display a success message to the user.
        // You can use Axios or any other method to make the necessary API requests.

        axios
          .get('/admin/close-day')
          .then((response) => {
            Swal.fire('Day Closed', 'The day has been closed successfully', 'success');
          })
          .catch((error) => {
            Swal.fire('Error', 'An error occurred while closing the day', 'error');
          });
      }

    handleCategorySelect(categoryId) {
        // Set the loading state to true
        this.setState({ isLoading: true });

        // Make an AJAX request to the API endpoint with the selected category ID
        axios.get(`/api/products/${categoryId}`).then((res) => {
            const filteredProducts = res.data;

            console.log(filteredProducts);

            // Update the state and set isLoading to false when data is received
            this.setState({ selectedCategory: categoryId, products: filteredProducts, isLoading: false });
        });
    }




    loadCategories() {
        axios.get(`/admin/categories`).then((res) => {
            const categories = res.data;
            this.setState({ categories });
        });
    }

    loadProducts(search = "") {
        const query = !!search ? `?search=${search}` : "";
        axios.get(`/admin/products${query}`).then((res) => {
            const products = res.data.data;
            this.setState({ products });
        });
    }

    handleOnChangeBarcode(event) {
        const barcode = event.target.value;
        console.log(barcode);
        this.setState({ barcode });
    }

    loadCart() {
        const { customer_id } = this.state;
        console.log('im customer: ' +customer_id );
        axios.get("/admin/cart", { customer_id }).then((res) => {
            const cart = res.data;
            console.log('im customer: ' +customer_id );
            this.setState({ cart });
        });
    }

    handleScanBarcode(event) {
        event.preventDefault();
        const { customer_id } = this.state;
        const { barcode } = this.state;
        if (!!barcode) {
            axios
                .post("/admin/cart", { barcode, customer_id })
                .then((res) => {
                    console.log('Barcode is: ' + barcode);
                    this.loadCart();
                    this.setState({ barcode: "" });
                })
                .catch((err) => {
                    Swal.fire("Error!", err.response.data.message, "error");
                });
        }
    }

    fetchCartItemsByCustomerId(customerId) {
        axios.get(`/admin/cart/customer/${customerId}`)
            .then((res) => {
                const cart = res.data;
                console.log(cart);
                this.setState({ cart });
            })
            .catch((err) => {
                console.error("fetchCartItemsByCustomerId method error", err);
            });
    }

    handleChangeQty(product_id, qty) {
        const { customer_id } = this.state;
        const cart = this.state.cart.map((c) => {
            if (c.id === product_id) {
                c.pivot.quantity = qty;
            }
            return c;
        });

        this.setState({ cart });
        if (!qty) return;

        axios
            .post("/admin/cart/change-qty", { product_id, quantity: qty, customer_id })
            .then((res) => {})
            .catch((err) => {
                Swal.fire("Error!", err.response.data.message, "error");
            });
    }

    getTotal(cart) {
        const total = cart.map((c) => c.pivot.quantity * c.price);
        return sum(total).toFixed(2);
    }
    handleClickDelete(product_id) {
        const { customer_id } = this.state;
        axios
            .post("/admin/cart/delete", { product_id, _method: "DELETE", customer_id })
            .then((res) => {
                const cart = this.state.cart.filter((c) => c.id !== product_id);
                this.setState({ cart });
            });
    }
    handleEmptyCart() {
        const { customer_id } = this.state;
        axios.post("/admin/cart/empty", { _method: "DELETE", customer_id }).then((res) => {
            this.setState({ cart: [] });
        });
    }
    handleChangeSearch(event) {
        const search = event.target.value;
        this.setState({ search });
    }
    handleSeach(event) {
        if (event.keyCode === 13) {
            this.loadProducts(event.target.value);
        }
    }

    componentWillUnmount() {
        window.removeEventListener('keydown', this.handleKeyPress);
        // ... (other componentWillUnmount code)
    }

    handleKeyPress = (event) => {
        if (event.keyCode === 113) {
            event.preventDefault();
            // Toggle the input focus state
            this.setState((prevState) => ({
                isInputFocused: !prevState.isInputFocused,
            }));

            if (this.state.isInputFocused) {
                // If input is focused, blur it
                this.barcodeInputRef.current.blur();
            } else {
                // If input is not focused, focus on it
                this.barcodeInputRef.current.focus();
            }
        }
    }


    addProductToCart(barcode) {
  const { orderItemId } = this.state;

  // Get product details from state or fetch from server if not available
  let product = this.state.products.find((p) => p.barcode === barcode);
  if (!product) {
    // Fetch product details from server if necessary
    axios.get(`/api/products/${barcode}`)
      .then((response) => {
        product = response.data;
        this.openQuantityModal(product); // Open modal with retrieved product
      })
      .catch((error) => {
        console.error("Error fetching product:", error);
        Swal.fire("Error!", "Product not found!", "error");
      });

    return; // Exit function if product is not available
  }

  axios.get(`/admin/minicart/${orderItemId}`)
    .then((response) => {
      const orderItem = response.data;
      // Extract order item details from the response
      const { prod_name, prod_id, id, quantity, order_id } = orderItem;

      Swal.fire({
        title: "Confirm Swap",
        text: `Are you sure you want to swap the ${prod_name} with ${product.name}?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Swap Now",
      }).then((result) => {
        if (result.isConfirmed) {
          // Pass order item details to openQuantityModal instead of product
          this.openQuantityModal({ prod_name, prod_id, id, quantity, order_id }, product);
        } else {
          this.props.history.push(previousPage); // Redirect to previous page
        }
      });
    })
    .catch((error) => {
      console.error("Error fetching product:", error);
      Swal.fire("Error!", "Product not found!", "error");
    });
}


openQuantityModal(orderItem, product)  {
  Swal.fire({
    title: `Enter Quantity for ${product.name}`,
    input: 'number',
    inputAttributes: {
      autocapitalize: 'off',
      min: 1,
      max: product.quantity, // Set max based on available stock
    },
    showCancelButton: true,
    confirmButtonText: 'Swap',
    preConfirm: (quantity) => {
      return new Promise((resolve) => {
        if (quantity <= 0 || quantity > product.quantity) {
          Swal.fire("Invalid Quantity Please enter a valid quantity.", error);
        } else {
          resolve(); // Quantity is valid, proceed with adding to cart
        }
      });
    }
  }).then((result) => {
    if (result.isConfirmed) {
        const swapItem = {
            ...product, // Include product details
            ...orderItem, // Include orderItem details
            pivot: { quantity: result.value, product_id: product.id, orderitem_id: orderItem.id, order_id: orderItem.order_id },
          };

      // Update cart on server and state (similar to before)
      axios.post('/admin/swap', swapItem)
        .then((response) => {
  // Redirect to the order details page
  window.location.href = `/admin/ordersedit/${orderItem.order_id}`;
})
.catch((error) => {
  console.error("Error swapping items:", error);
  Swal.fire("Error!", "An error occurred while swapping items", "error");
});
    }
  });
}




    setCustomerId(event) {
        const customer_id = event.target.value;
        this.setState({ customer_id });

        if (customer_id) {
            // Fetch cart items for the selected customer
            this.fetchCartItemsByCustomerId(customer_id);
        }
    }
    handleClickSubmit() {
        Swal.fire({
            title: "Total amount ZK " + this.getTotal(this.state.cart),
            text: "Enter amount received below ",
            input: "number",
            inputValue: this.getTotal(this.state.cart),
            showCancelButton: true,
            confirmButtonText: "Pay",
            showLoaderOnConfirm: true,
            preConfirm: (amount) => {
                return new Promise((resolve, reject) => {
                    const totalAmount = parseFloat(this.getTotal(this.state.cart));
                    if (parseFloat(amount) >= totalAmount) {
                        axios
                            .post("/admin/orders", {
                                customer_id: this.state.customer_id,
                                amount,
                            })
                            .then((res) => {
                                const orderId = res.data;
                                const receivedAmount = parseFloat(amount);
                                const changeAmount = receivedAmount - totalAmount;
                                this.setState({ changeAmount });

                                // Close the day and open the receipt tab here
                                this.handleCloseDay();
                                this.openReceiptTab(orderId, receivedAmount, changeAmount);

                                resolve({ orderId, receivedAmount });
                                console.log(res);
                            })
                            .catch((err) => {
                                Swal.showValidationMessage(err.response.data.message);
                                reject(err);
                            });
                    } else {
                        Swal.showValidationMessage("Amount must be greater than or equal to the total. PRESS F5 to close error message");
                        reject("Invalid amount");
                    }
                });
            },
            allowOutsideClick: () => Swal.isLoading(),
        });
    }

    openReceiptTab(orderId, receivedAmount, changeAmount) {
        // Include the receivedAmount and changeAmount as query parameters
        const receiptUrl = `/admin/orders/receipt?orderId=${orderId}&receivedAmount=${receivedAmount}&changeAmount=${changeAmount}`;
        window.open(receiptUrl, '_blank');

        window.location.reload();
    }

    render() {
        const { cart, products, customers, barcode } = this.state;

        console.log("Cart:", cart);
        return (
            <div className="row">

                <div className="col-md-8 col-lg-12">
                <div className="row mb-2">
                <div className="col">
                            <form onSubmit={this.handleScanBarcode}>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Scan Barcode..."
                                    value={barcode}
                                    onChange={this.handleOnChangeBarcode}
                                    ref={this.barcodeInputRef}
                                />
                            </form>
                        </div>
    <div className="col">
        <input
            type="text"
            className="form-control"
            placeholder="Search Product..."
            onChange={this.handleChangeSearch}
            onKeyDown={this.handleSeach}
        />
    </div>
    </div>

    <div className="card">
  <div className="category-container">
    {/* Add an "All" option */}
    <div
      className={
        this.state.selectedCategory === null
          ? "category-item selected"
          : "category-item"
      }
      onClick={() => this.handleCategorySelect(null)}
    >
      All
    </div>

    {/* Map through other categories */}
    {this.state.categories.map((category) => (
      <div
        key={category.id}
        className={
          category.id === this.state.selectedCategory
            ? "category-item selected"
            : "category-item"
        }
        onClick={() => this.handleCategorySelect(category.id)}
      >
        {category.name}
      </div>
    ))}
  </div>
</div>
<div className="orderprod-section">
<div className="scrollable-wrapper">
    <div className="order-product">

    {this.state.isLoading ? (
    <div>Loading...</div>
) : (
    this.state.products.map((p) => (
        // Check if the selected category matches the product's category
        (this.state.selectedCategory === null || this.state.selectedCategory === p.category_id) && (
            <div
                onClick={() => this.addProductToCart(p.barcode)}
                key={p.id}
                className="item"
            >



<div className="ribbon" style={
                        window.APP.warning_quantity > p.quantity
                            ? { background: "red" }
                            : {}
                    }>ZK {p.price}</div>

            {console.log(p.image_url)}
                <img src={p.image_url} alt="" />

                <h5
                    style={
                        window.APP.warning_quantity > p.quantity
                            ? { color: "red" }
                            : {}
                    }
                >
                    {p.name} ({p.quantity})
                </h5>
            </div>
        )
    ))
)}

    </div>
    </div>
    </div>
</div>


            </div>
        );
    }
}

export default MiniCart;

if (document.getElementById("minicart")) {
    ReactDOM.render(<MiniCart />, document.getElementById("minicart"));

}
