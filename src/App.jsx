import React, { useState } from 'react';
import { Store, UserCircle, ShoppingCart } from 'lucide-react';

const App = () => {
  const [products] = useState([
    { id: 1, name: 'Parle-G Biscuits', barcode: '8901234567890', price: 10, gst: 18, stock: 100, category: 'Snacks' },
    { id: 2, name: 'Dairy Milk Chocolate', barcode: '8901234567891', price: 20, gst: 12, stock: 50, category: 'Chocolates' },
    { id: 3, name: 'Nescafe Coffee', barcode: '8901234567892', price: 180, gst: 18, stock: 30, category: 'Beverages' },
  ]);

  const [cart, setCart] = useState([]);

  const addToCart = (product) => {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
      setCart(cart.map(item =>
        item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item
      ));
    } else {
      setCart([...cart, { ...product, quantity: 1 }]);
    }
  };

  const total = cart.reduce((sum, item) => {
    const itemTotal = item.price * item.quantity;
    const gst = (itemTotal * item.gst) / 100;
    return sum + itemTotal + gst;
  }, 0);

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-md">
        <div className="max-w-7xl mx-auto px-4 py-4">
          <div className="flex justify-between items-center">
            <div className="flex items-center">
              <Store className="text-blue-500 mr-3" size={32} />
              <h1 className="text-2xl font-bold text-gray-900">Easy-Retail</h1>
            </div>
            <div className="flex items-center text-sm text-gray-600">
              <UserCircle className="mr-2" size={18} />
              <span>Rajesh Kumar</span>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2">
            <div className="bg-white rounded-xl shadow-lg p-6">
              <h2 className="text-xl font-bold mb-4">Products</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {products.map(product => (
                  <div
                    key={product.id}
                    className="border rounded-lg p-4 hover:shadow-md cursor-pointer"
                    onClick={() => addToCart(product)}
                  >
                    <h3 className="font-semibold">{product.name}</h3>
                    <p className="text-sm text-gray-600">{product.category}</p>
                    <div className="flex justify-between items-center mt-2">
                      <span className="text-lg font-bold text-blue-600">₹{product.price}</span>
                      <span className="text-sm text-green-600">{product.gst}% GST</span>
                    </div>
                    <p className="text-xs text-gray-500 mt-1">Stock: {product.stock}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <div>
            <div className="bg-white rounded-xl shadow-lg p-6">
              <h2 className="text-xl font-bold mb-4 flex items-center">
                <ShoppingCart className="mr-2 text-blue-500" size={20} />
                Cart
              </h2>
              <div className="space-y-3">
                {cart.map(item => (
                  <div key={item.id} className="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <h4 className="font-medium text-sm">{item.name}</h4>
                      <p className="text-xs text-gray-600">₹{item.price} × {item.quantity}</p>
                    </div>
                    <span className="font-bold">₹{(item.price * item.quantity * (1 + item.gst/100)).toFixed(2)}</span>
                  </div>
                ))}
              </div>
              {cart.length > 0 && (
                <div className="mt-4 pt-4 border-t">
                  <div className="flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span>₹{total.toFixed(2)}</span>
                  </div>
                  <button className="w-full mt-4 bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600">
                    Process Payment
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default App;
