const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

// Connect to MongoDB
mongoose.connect('mongodb://localhost:27017/marketplace', { useNewUrlParser: true, useUnifiedTopology: true });

// Product Schema
const productSchema = new mongoose.Schema({
    type: String,
    name: String,
    description: String,
    price: Number,
    commission: Number,
    image: String,
    seller: {
        username: String,
        country: String,
        avatar: String,
        payMethods: [String]
    }
});
const Product = mongoose.model('Product', productSchema);

// Chat Schema
const chatSchema = new mongoose.Schema({
    productId: mongoose.Schema.Types.ObjectId,
    messages: [{ sender: String, text: String, timestamp: Date }]
});
const Chat = mongoose.model('Chat', chatSchema);

// Create a product
app.post('/products', async (req, res) => {
    try {
        const product = new Product(req.body);
        await product.save();
        res.status(201).json(product);
    } catch (err) {
        res.status(400).json({ error: err.message });
    }
});

// Get products (with optional search)
app.get('/products', async (req, res) => {
    const q = req.query.q ? req.query.q.toLowerCase() : '';
    const filter = q ? { $or: [
        { name: new RegExp(q, 'i') },
        { type: new RegExp(q, 'i') },
        { description: new RegExp(q, 'i') }
    ]} : {};
    const products = await Product.find(filter);
    res.json(products);
});

// Get a single product
app.get('/products/:id', async (req, res) => {
    const product = await Product.findById(req.params.id);
    if (!product) return res.status(404).json({ error: 'Product not found' });
    res.json(product);
});

// Update a product
app.put('/products/:id', async (req, res) => {
    const product = await Product.findByIdAndUpdate(req.params.id, req.body, { new: true });
    if (!product) return res.status(404).json({ error: 'Product not found' });
    res.json(product);
});

// Delete a product
app.delete('/products/:id', async (req, res) => {
    const product = await Product.findByIdAndDelete(req.params.id);
    if (!product) return res.status(404).json({ error: 'Product not found' });
    res.json({ message: 'Product deleted' });
});

// Get chat messages for a product
app.get('/chat/:productId', async (req, res) => {
    const chat = await Chat.findOne({ productId: req.params.productId });
    res.json(chat ? chat.messages : []);
});

// Add a chat message
app.post('/chat/:productId', async (req, res) => {
    const { sender, text } = req.body;
    let chat = await Chat.findOne({ productId: req.params.productId });
    if (!chat) chat = new Chat({ productId: req.params.productId, messages: [] });
    chat.messages.push({ sender, text, timestamp: new Date() });
    await chat.save();
    res.json(chat.messages);
});

// Delete all chat messages for a product
app.delete('/chat/:productId', async (req, res) => {
    await Chat.deleteOne({ productId: req.params.productId });
    res.json({ message: 'Chat deleted' });
});

app.listen(3000, () => console.log('Server running on http://localhost:3000'));