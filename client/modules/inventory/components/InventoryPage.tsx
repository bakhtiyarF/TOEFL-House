/**
 * Inventory Page — Inventory Module
 * Book management, sale recording, restocking, refund handling
 */

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@shared/components/ui/tabs';
import { Package, Plus, Search, BookOpen, ShoppingCart, RotateCcw, AlertTriangle, TrendingUp } from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const BookSchema = z.object({
  title: z.string().min(2, 'Title is required'),
  price: z.string().min(1, 'Price is required'),
  purchase_price: z.string().optional(),
  stock: z.string().optional(),
  is_chapter: z.boolean().optional(),
});

const SaleSchema = z.object({
  quantity: z.string().min(1, 'Quantity is required'),
  discount_amount: z.string().optional(),
  payment_method: z.enum(['cash', 'card', 'bank_transfer']),
  customer_name: z.string().optional(),
});

type BookFormValues = z.infer<typeof BookSchema>;
type SaleFormValues = z.infer<typeof SaleSchema>;

interface Book {
  id: string;
  title: string;
  price: number;
  purchase_price: number;
  stock: number;
  is_chapter: boolean;
  total_sold: number;
}

interface Sale {
  id: string;
  book_title: string;
  quantity: number;
  total_amount: number;
  net_amount: number;
  payment_method: string;
  status: string;
  date: string;
  customer_name: string;
}

const mockBooks: Book[] = [
  { id: '1', title: 'Official TOEFL Guide 5th Ed.', price: 800, purchase_price: 500, stock: 45, is_chapter: false, total_sold: 120 },
  { id: '2', title: "Barron's TOEFL iBT", price: 1200, purchase_price: 750, stock: 23, is_chapter: false, total_sold: 85 },
  { id: '3', title: 'Reading Skills - Level 1', price: 250, purchase_price: 150, stock: 67, is_chapter: true, total_sold: 200 },
  { id: '4', title: 'Writing Skills - Level 2', price: 300, purchase_price: 180, stock: 34, is_chapter: true, total_sold: 150 },
  { id: '5', title: 'Cambridge IELTS 17', price: 900, purchase_price: 600, stock: 12, is_chapter: false, total_sold: 60 },
  { id: '6', title: 'Grammar in Use', price: 650, purchase_price: 400, stock: 0, is_chapter: false, total_sold: 95 },
  { id: '7', title: 'Listening Skills - Level 3', price: 280, purchase_price: 160, stock: 41, is_chapter: true, total_sold: 110 },
  { id: '8', title: 'Speaking Practice Book', price: 350, purchase_price: 200, stock: 8, is_chapter: true, total_sold: 75 },
];

const mockSales: Sale[] = [
  { id: '1', book_title: 'Official TOEFL Guide 5th Ed.', quantity: 2, total_amount: 1600, net_amount: 1600, payment_method: 'cash', status: 'completed', date: '2026-08-22', customer_name: 'Ahmad Rahimi' },
  { id: '2', book_title: 'Reading Skills - Level 1', quantity: 1, total_amount: 250, net_amount: 250, payment_method: 'cash', status: 'completed', date: '2026-08-22', customer_name: 'Walk-in' },
  { id: '3', book_title: "Barron's TOEFL iBT", quantity: 1, total_amount: 1200, net_amount: 1100, payment_method: 'card', status: 'completed', date: '2026-08-21', customer_name: 'Fatima Ahmadi' },
  { id: '4', book_title: 'Grammar in Use', quantity: 1, total_amount: 650, net_amount: 650, payment_method: 'cash', status: 'refunded', date: '2026-08-20', customer_name: 'Sara Mohammadi' },
  { id: '5', book_title: 'Cambridge IELTS 17', quantity: 3, total_amount: 2700, net_amount: 2500, payment_method: 'bank_transfer', status: 'completed', date: '2026-08-19', customer_name: 'Bulk order - Herat Branch' },
];

export function InventoryPage() {
  const [books, setBooks] = useState(mockBooks);
  const [searchQuery, setSearchQuery] = useState('');
  const [isAddBookOpen, setIsAddBookOpen] = useState(false);
  const [isSaleOpen, setIsSaleOpen] = useState(false);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);

  const filteredBooks = books.filter((b) =>
    b.title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const stats = {
    totalBooks: books.length,
    totalStock: books.reduce((s, b) => s + b.stock, 0),
    outOfStock: books.filter((b) => b.stock === 0).length,
    lowStock: books.filter((b) => b.stock > 0 && b.stock < 10).length,
    totalRevenue: mockSales.filter(s => s.status === 'completed').reduce((s, sale) => s + sale.net_amount, 0),
  };

  const bookForm = useForm<BookFormValues>({ resolver: zodResolver(BookSchema) });
  const saleForm = useForm<SaleFormValues>({
    resolver: zodResolver(SaleSchema),
    defaultValues: { payment_method: 'cash', quantity: '1', discount_amount: '0' },
  });

  const onAddBook = (data: BookFormValues) => {
    const newBook: Book = {
      id: String(books.length + 1),
      title: data.title,
      price: Number(data.price),
      purchase_price: Number(data.purchase_price) || 0,
      stock: Number(data.stock) || 0,
      is_chapter: data.is_chapter || false,
      total_sold: 0,
    };
    setBooks([...books, newBook]);
    setIsAddBookOpen(false);
    bookForm.reset();
  };

  const onSale = (data: SaleFormValues) => {
    if (!selectedBook) return;
    const qty = Number(data.quantity);
    if (qty > selectedBook.stock) return;
    const totalAmount = selectedBook.price * qty;
    const discount = Number(data.discount_amount) || 0;
    const netAmount = totalAmount - discount;

    setBooks(books.map((b) => b.id === selectedBook.id ? { ...b, stock: b.stock - qty, total_sold: b.total_sold + qty } : b));
    setIsSaleOpen(false);
    setSelectedBook(null);
    saleForm.reset();
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Package className="h-8 w-8" />
            Inventory
          </h1>
          <p className="text-muted-foreground">Manage books, sales, and stock</p>
        </div>
        <div className="flex gap-2">
          <Dialog open={isAddBookOpen} onOpenChange={setIsAddBookOpen}>
            <DialogTrigger asChild>
              <Button variant="outline"><Plus className="h-4 w-4 me-2" /> Add Book</Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Add New Book</DialogTitle>
                <DialogDescription>Register a new book or chapter in inventory</DialogDescription>
              </DialogHeader>
              <form onSubmit={bookForm.handleSubmit(onAddBook)} className="space-y-4">
                <div className="space-y-2">
                  <Label>Title *</Label>
                  <Input placeholder="Book title" {...bookForm.register('title')} />
                </div>
                <div className="grid grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label>Sale Price (AFN) *</Label>
                    <Input type="number" {...bookForm.register('price')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Purchase Price</Label>
                    <Input type="number" {...bookForm.register('purchase_price')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Initial Stock</Label>
                    <Input type="number" {...bookForm.register('stock')} />
                  </div>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsAddBookOpen(false)}>Cancel</Button>
                  <Button type="submit">Add Book</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-5">
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold">{stats.totalBooks}</div><p className="text-xs text-muted-foreground">Total Books</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold">{stats.totalStock}</div><p className="text-xs text-muted-foreground">Total Stock</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold text-red-600">{stats.outOfStock}</div><p className="text-xs text-muted-foreground">Out of Stock</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold text-orange-600">{stats.lowStock}</div><p className="text-xs text-muted-foreground">Low Stock (&lt;10)</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold text-green-600">{formatAmount(stats.totalRevenue)}</div><p className="text-xs text-muted-foreground">Sales Revenue (AFN)</p></CardContent></Card>
      </div>

      <Tabs defaultValue="books">
        <TabsList>
          <TabsTrigger value="books">Books & Stock</TabsTrigger>
          <TabsTrigger value="sales">Sales History</TabsTrigger>
        </TabsList>

        {/* Books Tab */}
        <TabsContent value="books">
          <Card>
            <CardHeader>
              <div className="relative max-w-md">
                <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input placeholder="Search books..." className="ps-10" value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead className="text-end">Price</TableHead>
                    <TableHead className="text-end">Cost</TableHead>
                    <TableHead className="text-center">Stock</TableHead>
                    <TableHead className="text-center">Sold</TableHead>
                    <TableHead className="text-end">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredBooks.map((book) => (
                    <TableRow key={book.id}>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <BookOpen className="h-4 w-4 text-muted-foreground" />
                          <span className="font-medium">{book.title}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant={book.is_chapter ? 'secondary' : 'default'}>
                          {book.is_chapter ? 'Chapter' : 'Book'}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-end font-mono">{formatAmount(book.price)}</TableCell>
                      <TableCell className="text-end font-mono text-muted-foreground">{formatAmount(book.purchase_price)}</TableCell>
                      <TableCell className="text-center">
                        <Badge variant={book.stock === 0 ? 'destructive' : book.stock < 10 ? 'secondary' : 'default'}>
                          {book.stock === 0 ? (
                            <span className="flex items-center gap-1"><AlertTriangle className="h-3 w-3" /> Out</span>
                          ) : book.stock}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center text-muted-foreground">{book.total_sold}</TableCell>
                      <TableCell className="text-end">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            variant="outline"
                            size="sm"
                            disabled={book.stock === 0}
                            onClick={() => { setSelectedBook(book); setIsSaleOpen(true); }}
                          >
                            <ShoppingCart className="h-3 w-3 me-1" /> Sell
                          </Button>
                          <Button variant="outline" size="sm">
                            <Plus className="h-3 w-3 me-1" /> Restock
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Sales Tab */}
        <TabsContent value="sales">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <TrendingUp className="h-5 w-5" />
                Sales History
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Book</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead className="text-center">Qty</TableHead>
                    <TableHead className="text-end">Total</TableHead>
                    <TableHead className="text-end">Net</TableHead>
                    <TableHead>Method</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {mockSales.map((sale) => (
                    <TableRow key={sale.id}>
                      <TableCell className="text-muted-foreground">{sale.date}</TableCell>
                      <TableCell className="font-medium">{sale.book_title}</TableCell>
                      <TableCell className="text-muted-foreground">{sale.customer_name}</TableCell>
                      <TableCell className="text-center">{sale.quantity}</TableCell>
                      <TableCell className="text-end font-mono">{formatAmount(sale.total_amount)}</TableCell>
                      <TableCell className="text-end font-mono font-medium">{formatAmount(sale.net_amount)}</TableCell>
                      <TableCell><Badge variant="outline" className="capitalize">{sale.payment_method.replace('_', ' ')}</Badge></TableCell>
                      <TableCell>
                        <Badge variant={sale.status === 'completed' ? 'default' : 'destructive'}>
                          {sale.status}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Sale Dialog */}
      <Dialog open={isSaleOpen} onOpenChange={(open) => { setIsSaleOpen(open); if (!open) { setSelectedBook(null); saleForm.reset(); } }}>
        <DialogContent>
          {selectedBook && (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <ShoppingCart className="h-5 w-5" />
                  Record Sale
                </DialogTitle>
                <DialogDescription>
                  {selectedBook.title} — {formatAmount(selectedBook.price)} AFN (Stock: {selectedBook.stock})
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={saleForm.handleSubmit(onSale)} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Quantity *</Label>
                    <Input type="number" min="1" max={selectedBook.stock} {...saleForm.register('quantity')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Discount (AFN)</Label>
                    <Input type="number" min="0" {...saleForm.register('discount_amount')} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Payment Method</Label>
                  <Select defaultValue="cash" onValueChange={(v) => saleForm.register('payment_method').onChange({ target: { value: v } })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="cash">Cash</SelectItem>
                      <SelectItem value="card">Card</SelectItem>
                      <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Customer Name</Label>
                  <Input placeholder="Customer name (optional)" {...saleForm.register('customer_name')} />
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => { setIsSaleOpen(false); setSelectedBook(null); }}>Cancel</Button>
                  <Button type="submit"><ShoppingCart className="h-4 w-4 me-2" /> Complete Sale</Button>
                </DialogFooter>
              </form>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
