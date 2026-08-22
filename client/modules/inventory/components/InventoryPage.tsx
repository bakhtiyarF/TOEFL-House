/**
 * Inventory Page — Inventory Module
 * Fully live: books, stock, sales, restock, sell (wired to backend BookSaleService + controllers)
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
import { Package, Plus, Search, BookOpen, ShoppingCart, AlertTriangle, TrendingUp } from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';
import {
  useBooks,
  useBookSales,
  useCreateBook,
  useRestockBook,
  useSellBook,
} from '../hooks/useInventory';

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

export function InventoryPage() {
  const { data: books = [], isLoading: booksLoading } = useBooks();
  const { data: sales = [] } = useBookSales();

  const createBook = useCreateBook();
  const restockBook = useRestockBook();
  const sellBook = useSellBook();

  const [searchQuery, setSearchQuery] = useState('');
  const [isAddBookOpen, setIsAddBookOpen] = useState(false);
  const [isSaleOpen, setIsSaleOpen] = useState(false);
  const [selectedBook, setSelectedBook] = useState<any>(null);
  const [isRestockOpen, setIsRestockOpen] = useState(false);

  const filteredBooks = books.filter((b: any) =>
    b.title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  // Live stats
  const stats = {
    totalBooks: books.length,
    totalStock: books.reduce((s: number, b: any) => s + (b.stock || 0), 0),
    outOfStock: books.filter((b: any) => (b.stock || 0) === 0).length,
    lowStock: books.filter((b: any) => (b.stock || 0) > 0 && (b.stock || 0) < 10).length,
    totalRevenue: sales
      .filter((s: any) => s.status === 'completed')
      .reduce((s: number, sale: any) => s + (sale.net_amount || 0), 0),
  };

  const bookForm = useForm<BookFormValues>({
    resolver: zodResolver(BookSchema),
    defaultValues: { is_chapter: false },
  });

  const saleForm = useForm<SaleFormValues>({
    resolver: zodResolver(SaleSchema),
    defaultValues: { payment_method: 'cash', quantity: '1', discount_amount: '0' },
  });

  const restockForm = useForm<{ quantity: string; price?: string; purchase_price?: string }>({
    defaultValues: { quantity: '1' },
  });

  const onAddBook = (data: BookFormValues) => {
    createBook.mutate(
      {
        title: data.title,
        price: parseFloat(data.price),
        purchase_price: data.purchase_price ? parseFloat(data.purchase_price) : undefined,
        stock: data.stock ? parseInt(data.stock) : 0,
        is_chapter: data.is_chapter || false,
        branch_id: 'branch-1', // from auth context ideally
      },
      {
        onSuccess: () => {
          setIsAddBookOpen(false);
          bookForm.reset();
        },
      }
    );
  };

  const onSell = (data: SaleFormValues) => {
    if (!selectedBook) return;

    const qty = parseInt(data.quantity);
    const discount = parseFloat(data.discount_amount || '0');

    sellBook.mutate(
      {
        id: selectedBook.id,
        data: {
          quantity: qty,
          discount_amount: discount,
          payment_method: data.payment_method,
          customer_name: data.customer_name || undefined,
          // student_id can be added later via student picker
        },
      },
      {
        onSuccess: () => {
          setIsSaleOpen(false);
          setSelectedBook(null);
          saleForm.reset();
        },
      }
    );
  };

  const onRestock = (data: { quantity: string; price?: string; purchase_price?: string }) => {
    if (!selectedBook) return;

    restockBook.mutate(
      {
        id: selectedBook.id,
        data: {
          quantity: parseInt(data.quantity),
          price: data.price ? parseFloat(data.price) : undefined,
          purchase_price: data.purchase_price ? parseFloat(data.purchase_price) : undefined,
        },
      },
      {
        onSuccess: () => {
          setIsRestockOpen(false);
          setSelectedBook(null);
          restockForm.reset();
        },
      }
    );
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Package className="h-8 w-8" />
            Inventory
          </h1>
          <p className="text-muted-foreground">Manage books, sales, and stock (live)</p>
        </div>
        <div className="flex gap-2">
          <Dialog open={isAddBookOpen} onOpenChange={setIsAddBookOpen}>
            <DialogTrigger asChild>
              <Button variant="outline">
                <Plus className="h-4 w-4 me-2" /> Add Book
              </Button>
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
                    <Input type="number" step="0.01" {...bookForm.register('price')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Purchase Price</Label>
                    <Input type="number" step="0.01" {...bookForm.register('purchase_price')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Initial Stock</Label>
                    <Input type="number" {...bookForm.register('stock')} />
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <input type="checkbox" {...bookForm.register('is_chapter')} id="is_chapter" />
                  <Label htmlFor="is_chapter">Is Chapter Pack</Label>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsAddBookOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit" disabled={createBook.isPending}>
                    {createBook.isPending ? 'Adding...' : 'Add Book'}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {/* Stats - LIVE */}
      <div className="grid gap-4 md:grid-cols-5">
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{stats.totalBooks}</div>
            <p className="text-xs text-muted-foreground">Total Books</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{stats.totalStock}</div>
            <p className="text-xs text-muted-foreground">Total Stock</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-red-600">{stats.outOfStock}</div>
            <p className="text-xs text-muted-foreground">Out of Stock</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-orange-600">{stats.lowStock}</div>
            <p className="text-xs text-muted-foreground">Low Stock (&lt;10)</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-green-600">{formatAmount(stats.totalRevenue)}</div>
            <p className="text-xs text-muted-foreground">Sales Revenue (AFN)</p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="books">
        <TabsList>
          <TabsTrigger value="books">Books &amp; Stock</TabsTrigger>
          <TabsTrigger value="sales">Sales History</TabsTrigger>
        </TabsList>

        {/* Books Tab - LIVE */}
        <TabsContent value="books">
          <Card>
            <CardHeader>
              <div className="relative max-w-md">
                <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search books..."
                  className="ps-10"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
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
                  {booksLoading ? (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8">Loading books...</TableCell>
                    </TableRow>
                  ) : filteredBooks.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                        No books found.
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredBooks.map((book: any) => (
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
                        <TableCell className="text-end font-mono text-muted-foreground">
                          {formatAmount(book.purchase_price || 0)}
                        </TableCell>
                        <TableCell className="text-center">
                          <Badge
                            variant={
                              book.stock === 0 ? 'destructive' : book.stock < 10 ? 'secondary' : 'default'
                            }
                          >
                            {book.stock === 0 ? (
                              <span className="flex items-center gap-1">
                                <AlertTriangle className="h-3 w-3" /> Out
                              </span>
                            ) : (
                              book.stock
                            )}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-center text-muted-foreground">
                          {book.total_sold ?? 0}
                        </TableCell>
                        <TableCell className="text-end">
                          <div className="flex items-center justify-end gap-1">
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={book.stock === 0}
                              onClick={() => {
                                setSelectedBook(book);
                                setIsSaleOpen(true);
                              }}
                            >
                              <ShoppingCart className="h-3 w-3 me-1" /> Sell
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => {
                                setSelectedBook(book);
                                setIsRestockOpen(true);
                              }}
                            >
                              <Plus className="h-3 w-3 me-1" /> Restock
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Sales Tab - LIVE */}
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
                  {sales.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                        No sales recorded yet.
                      </TableCell>
                    </TableRow>
                  ) : (
                    sales.map((sale: any) => (
                      <TableRow key={sale.id}>
                        <TableCell className="text-muted-foreground">{sale.date}</TableCell>
                        <TableCell className="font-medium">{sale.book_title || sale.book_id}</TableCell>
                        <TableCell className="text-muted-foreground">{sale.customer_name || '—'}</TableCell>
                        <TableCell className="text-center">{sale.quantity}</TableCell>
                        <TableCell className="text-end font-mono">{formatAmount(sale.total_amount)}</TableCell>
                        <TableCell className="text-end font-mono font-medium">{formatAmount(sale.net_amount)}</TableCell>
                        <TableCell>
                          <Badge variant="outline" className="capitalize">
                            {sale.payment_method?.replace('_', ' ')}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <Badge variant={sale.status === 'completed' ? 'default' : 'destructive'}>
                            {sale.status}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Sale Dialog - LIVE */}
      <Dialog
        open={isSaleOpen}
        onOpenChange={(open) => {
          setIsSaleOpen(open);
          if (!open) {
            setSelectedBook(null);
            saleForm.reset();
          }
        }}
      >
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
              <form onSubmit={saleForm.handleSubmit(onSell)} className="space-y-4">
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
                  <Select
                    defaultValue="cash"
                    onValueChange={(v) => saleForm.setValue('payment_method', v as any)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
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
                  <Button type="button" variant="outline" onClick={() => setIsSaleOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit" disabled={sellBook.isPending}>
                    {sellBook.isPending ? 'Processing...' : 'Complete Sale'}
                  </Button>
                </DialogFooter>
              </form>
            </>
          )}
        </DialogContent>
      </Dialog>

      {/* Restock Dialog */}
      <Dialog
        open={isRestockOpen}
        onOpenChange={(open) => {
          setIsRestockOpen(open);
          if (!open) {
            setSelectedBook(null);
            restockForm.reset();
          }
        }}
      >
        <DialogContent>
          {selectedBook && (
            <>
              <DialogHeader>
                <DialogTitle>Restock: {selectedBook.title}</DialogTitle>
                <DialogDescription>Current stock: {selectedBook.stock}</DialogDescription>
              </DialogHeader>
              <form onSubmit={restockForm.handleSubmit(onRestock)} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Quantity *</Label>
                    <Input type="number" min="1" {...restockForm.register('quantity')} />
                  </div>
                  <div className="space-y-2">
                    <Label>New Price (optional)</Label>
                    <Input type="number" step="0.01" {...restockForm.register('price')} />
                  </div>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsRestockOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit" disabled={restockBook.isPending}>
                    {restockBook.isPending ? 'Restocking...' : 'Restock'}
                  </Button>
                </DialogFooter>
              </form>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
