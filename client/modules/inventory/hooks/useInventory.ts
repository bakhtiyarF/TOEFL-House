/**
 * Inventory Module - TanStack Query Hooks
 * Live data for books, sales, restock, sell
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { inventoryApi, type Book, type BookSale } from '../api';

export const inventoryKeys = {
  all: ['inventory'] as const,
  books: (p?: any) => [...inventoryKeys.all, 'books', p] as const,
  sales: (p?: any) => [...inventoryKeys.all, 'sales', p] as const,
};

export function useBooks(params?: { search?: string; branch_id?: string }) {
  return useQuery({
    queryKey: inventoryKeys.books(params),
    queryFn: () => inventoryApi.books.list(params),
    staleTime: 30_000,
  });
}

export function useBookSales(params?: any) {
  return useQuery({
    queryKey: inventoryKeys.sales(params),
    queryFn: () => inventoryApi.sales.list(params),
    staleTime: 60_000,
  });
}

export function useCreateBook() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: inventoryApi.books.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: inventoryKeys.books() });
    },
  });
}

export function useRestockBook() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: { quantity: number; price?: number; purchase_price?: number } }) =>
      inventoryApi.books.restock(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: inventoryKeys.books() }),
  });
}

export function useSellBook() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: { quantity: number; discount_amount?: number; payment_method: string; customer_name?: string; student_id?: string } }) =>
      inventoryApi.books.sell(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: inventoryKeys.books() });
      qc.invalidateQueries({ queryKey: inventoryKeys.sales() });
      qc.invalidateQueries({ queryKey: ['finance'] }); // sync finance
    },
  });
}

export function useRefundSale() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (saleId: string) => inventoryApi.sales.refund(saleId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: inventoryKeys.sales() });
      qc.invalidateQueries({ queryKey: inventoryKeys.books() });
    },
  });
}
