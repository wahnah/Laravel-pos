import React, { Component } from "react";
import ReactDOM from "react-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { sum } from "lodash";

class Cotation extends Component {
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
        this.handleCategorySelect = this.handleCategorySelect.bind(this);
        this.setCustomerId = this.setCustomerId.bind(this);
        this.handleClickSubmit = this.handleClickSubmit.bind(this);
        this.barcodeInputRef = React.createRef();
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



    handleCategorySelect(categoryId) {
        // Set the loading state to true
        this.setState({ isLoading: true });

        // Make an AJAX request to the API endpoint with the selected category ID
        axios.get(`/api/products/${categoryId}`).then((res) => {
            const filteredProducts = res.data;

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

    addProductToCart(barcode) {
        const { customer_id } = this.state; // Get the customer_id from the state
    console.log("customer_id:", customer_id);
        let product = this.state.products.find((p) => p.barcode === barcode);
        if (!!product) {
            // if product is already in cart
            let cart = this.state.cart.find((c) => c.id === product.id);
            if (!!cart) {
                // update quantity
                this.setState({
                    cart: this.state.cart.map((c) => {
                        if (
                            c.id === product.id &&
                            product.quantity > c.pivot.quantity
                        ) {
                            c.pivot.quantity = c.pivot.quantity + 1;
                        }
                        return c;
                    }),
                });
            } else {
                if (product.quantity > 0) {
                    product = {
                        ...product,
                        pivot: {
                            quantity: 1,
                            product_id: product.id,
                            user_id: 1,
                            customer_id: customer_id,// Include the customer_id
                        },
                    };

                    this.setState({ cart: [...this.state.cart, product] });
                }
            }

            axios
                .post("/admin/cart", { barcode, customer_id }) // Send customer_id to the server
                .then((res) => {
                    // this.loadCart();
                    console.log(res);
                })
                .catch((err) => {
                    Swal.fire("Error!", err.response.data.message, "error");
                });
        }
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
            title: "Received Amount",
            input: "text",
            inputValue: this.getTotal(this.state.cart),
            showCancelButton: true,
            confirmButtonText: "Send",
            showLoaderOnConfirm: true,
            preConfirm: (amount) => {
                return new Promise((resolve, reject) => {
                    axios
                        .post("/admin/orders", {
                            customer_id: this.state.customer_id,
                            amount,
                        })
                        .then((res) => {
                            const orderId = res.data;
                            const receivedAmount = parseFloat(amount);
                            const totalAmount = parseFloat(this.getTotal(this.state.cart));
                            const changeAmount = receivedAmount - totalAmount;
                            this.setState({ changeAmount }); // Store the change amount in state
                            this.loadCart();
                            resolve({ orderId, receivedAmount }); // Resolve the Promise with orderId
                            console.log(res);
                        })
                        .catch((err) => {
                            Swal.showValidationMessage(err.response.data.message);
                            reject(err);
                        });
                });
            },
            allowOutsideClick: () => !Swal.isLoading(),
        }).then((result) => {
            if (result.value) {
                const { orderId, receivedAmount } = result.value; // Get orderId from resolved Promise
                // Show a new SweetAlert dialogue with the change amount
                Swal.fire({
                    title: "Change Amount",
                    text: `Change: ${window.APP.currency_symbol} ${this.state.changeAmount.toFixed(2)}`,
                    icon: "success",
                    confirmButtonText: "OK",
                }).then(() => {
                    // Open the receipt tab after the "OK" button is clicked
                    console.log(orderId);
                    this.openReceiptTab(orderId, receivedAmount, this.state.changeAmount);
                });
            }
        });
    }
    openReceiptTab(orderId, receivedAmount, changeAmount) {
        // Include the receivedAmount and changeAmount as query parameters
        const receiptUrl = `/admin/orders/receipt?orderId=${orderId}&receivedAmount=${receivedAmount}&changeAmount=${changeAmount}`;
        window.open(receiptUrl, '_blank');
    }

    render() {
        const { cart, products, customers, barcode } = this.state;

        console.log("Cart:", cart);
        return (
            <div className="row">
                <div className="col-md-6 col-lg-4">
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
                            <select
                                className="form-control"
                                onChange={this.setCustomerId}
                            >

                                {customers.map((cus) => (
                                    <option
                                        key={cus.id}
                                        value={cus.id}
                                    >{`${cus.first_name} ${cus.last_name}`}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="user-cart">
                <div className="card">
                    <table className="table table-striped">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th className="text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            {cart.map((c) => {
                                // Debugging: Log each item in the cart
                                console.log("Cart Item:", c);

                                // Add a null check for c.pivot
                                const quantity = c.pivot ? c.pivot.quantity : '';

                                return (
                                    <tr key={c.id}>
                                        <td>{c.name}</td>
                                        <td>
                                            <input
                                                type="text"
                                                className="form-control form-control-sm qty"
                                                value={quantity}
                                                onChange={(event) =>
                                                    this.handleChangeQty(
                                                        c.id,
                                                        event.target.value
                                                    )
                                                }
                                            />
                                            <button
                                                className="btn btn-danger btn-sm"
                                                onClick={() =>
                                                    this.handleClickDelete(
                                                        c.id
                                                    )
                                                }
                                            >
                                                <i className="fas fa-trash"></i>
                                            </button>
                                        </td>
                                        <td className="text-right">
                                            {window.APP.currency_symbol}{" "}
                                            {(c.price * quantity).toFixed(2)}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>


                    <div className="row">
                        <div className="col">Total:</div>
                        <div className="col text-right">
                            {window.APP.currency_symbol} {this.getTotal(cart)}
                        </div>
                    </div>
                    <div className="row">
                        <div className="col">
                            <button
                                type="button"
                                className="btn btn-danger btn-block"
                                onClick={this.handleEmptyCart}
                                disabled={!cart.length}
                            >
                                Cancel
                            </button>
                        </div>
                        <div className="col">
                            <button
                                type="button"
                                className="btn btn-primary btn-block"
                                disabled={!cart.length}
                                onClick={this.handleClickSubmit}
                            >
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
                <div className="col-md-6 col-lg-8">
    <div className="mb-2">
        <input
            type="text"
            className="form-control"
            placeholder="Search Product..."
            onChange={this.handleChangeSearch}
            onKeyDown={this.handleSeach}
        />
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
    <div className="order-product">

    {this.state.isLoading ? (
    <div>Loading...</div>
) : (
    this.state.products.map((p) => (
        // Check if the selected category matches the product's category
        (this.state.selectedCategory === null || p.category_id === this.state.selectedCategory) && (
            <div
                onClick={() => this.addProductToCart(p.barcode)}
                key={p.id}
                className="item"
            >

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
        );
    }
}

export default Cotation;

if (document.getElementById("cotation")) {
    ReactDOM.render(<Cotation />, document.getElementById("cotation"));
}
