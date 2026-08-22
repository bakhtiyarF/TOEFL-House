/**
 * Inventory Page — Inventory Module (Books & Sales)
 */

import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Package, Plus, Search, BookOpen, ShoppingCart } from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const mockBooks = [
  { id: '1', title: 'Official TOEFL Guide 5th Ed.', price: 800, stock: 45, isChapter: false },
  { id: '2', title: 'Barron\'s TOEFL iBT', price: 1200, stock: 23, isChapter: false },
  { id: '3', title: 'Reading Skills - Level 1', price: 250, stock: 67, isChapter: true },
  { id: '4', title: 'Writing Skills - Level 2', price: 300, stock: 34, isChapter: true },
  { id: '5', title: 'Cambridge IELTS 17', price: 900, stock: 12, isChapter: false },
  { id: '6', title: 'Grammar in Use', price: 650, stock: 0, isChapter: false },
];

export function InventoryPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Package className="h-8 w-8" />
            Inventory
          </h1>
          <p className="text-muted-foreground">Manage books and sales</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline">
            <ShoppingCart className="h-4 w-4 me-2" />
            Record Sale
          </Button>
          <Button>
            <Plus className="h-4 w-4 me-2" />
            Add Book
          </Button>
        </div>
      </div>

      {/* Summary */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Total Books</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{mockBooks.length}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Total Stock</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{mockBooks.reduce((sum, b) => sum + b.stock, 0)}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Out of Stock</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">
              {mockBooks.filter((b) => b.stock === 0).length}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Books Table */}
      <Card>
        <CardHeader>
          <div className="relative">
            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Search books..." className="ps-10" />
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="text-start py-3 px-4 font-medium">Title</th>
                  <th className="text-start py-3 px-4 font-medium">Type</th>
                  <th className="text-end py-3 px-4 font-medium">Price (AFN)</th>
                  <th className="text-end py-3 px-4 font-medium">Stock</th>
                  <th className="text-end py-3 px-4 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {mockBooks.map((book) => (
                  <tr key={book.id} className="border-b hover:bg-muted/50">
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <BookOpen className="h-4 w-4 text-muted-foreground" />
                        {book.title}
                      </div>
                    </td>
                    <td className="py-3 px-4">
                      <Badge variant={book.isChapter ? 'secondary' : 'default'}>
                        {book.isChapter ? 'Chapter' : 'Book'}
                      </Badge>
                    </td>
                    <td className="py-3 px-4 text-end font-mono">{formatAmount(book.price)}</td>
                    <td className="py-3 px-4 text-end">
                      <Badge variant={book.stock === 0 ? 'destructive' : book.stock < 10 ? 'secondary' : 'default'}>
                        {book.stock}
                      </Badge>
                    </td>
                    <td className="py-3 px-4 text-end">
                      <Button variant="ghost" size="sm">Manage</Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
