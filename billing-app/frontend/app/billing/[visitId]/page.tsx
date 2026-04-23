"use client";

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Card, CardContent, Button } from '../../../components/ui-mock';
import { Loader2, CheckCircle2, UserCircle, CreditCard, Banknote } from 'lucide-react';

const RadioGroup = ({ children, onValueChange }: any) => <div onChange={(e: any) => onValueChange(e.target.value)}>{children}</div>;
const RadioGroupItem = ({ value, id, className }: any) => <input type="radio" value={value} id={id} name="insurance" className={className} />;
const Label = ({ children, htmlFor, className }: any) => <label htmlFor={htmlFor} className={className}>{children}</label>;

interface InvoiceItem {
  id: string;
  description: string;
  qty: number;
  price: number;
  total: number;
}

interface Payment {
  id: string;
  amount: number;
  method: string;
  status: string;
  transaction_id?: string;
}

interface Invoice {
  id: string;
  patient_name: string;
  visit_id: string;
  status: string;
  total_amount: number;
  items: InvoiceItem[];
  payments: Payment[];
}

export default function BillingPage({ params }: { params: { visitId: string } }) {
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [insurance, setInsurance] = useState("none");
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    fetchInvoice();
  }, [params.visitId]);

  // Polling logic for status updates (Real Integration)
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (invoice && (invoice.status === 'pending' || hasPendingMomo(invoice))) {
      interval = setInterval(fetchInvoice, 3000); 
    }
    return () => clearInterval(interval);
  }, [invoice]);

  const fetchInvoice = async () => {
    try {
      const res = await fetch(`/api/visits/${params.visitId}/active-invoice`);
      if (!res.ok) return;
      const data = await res.json();
      setInvoice(data);
    } catch (e) {
      console.error("Connection failed.");
    } finally {
      setLoading(false);
    }
  };

  const hasPendingMomo = (inv: Invoice) => 
    inv.payments.some(p => p.method === 'mobile_money' && p.status === 'pending');

  const pendingPayment = invoice?.payments.find(p => p.status === 'pending');

  const calculateRemaining = () => {
    if (!invoice) return 0;
    const paid = invoice.payments
      .filter(p => p.status === 'confirmed')
      .reduce((sum, p) => sum + p.amount, 0);
    return invoice.total_amount - paid;
  };

  const handlePay = async (method: 'cash' | 'mobile_money') => {
    if (!invoice) return;
    setProcessing(true);
    try {
      await fetch(`/api/invoices/${invoice.id}/payments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: calculateRemaining(), method })
      });
      fetchInvoice();
    } catch (e) {
      console.error("Payment initiation failed.");
    } finally {
      setProcessing(false);
    }
  };

  if (loading) return <div className="flex h-screen items-center justify-center bg-gray-50"><Loader2 className="animate-spin text-blue-600" /></div>;
  if (!invoice) return <div className="p-8">No invoice found for this visit.</div>;

  if (invoice.status === 'paid' && !processing) {
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/5 backdrop-blur-sm">
        <Card className="w-[400px] p-10 text-center shadow-2xl border-none">
          <div className="flex justify-center mb-6">
            <div className="bg-emerald-100 p-4 rounded-full">
              <CheckCircle2 size={48} className="text-emerald-500" />
            </div>
          </div>
          <h2 className="text-2xl font-bold mb-2">Payment Successful</h2>
          <p className="text-gray-500 mb-8 font-medium">Amount: {invoice.total_amount.toLocaleString()} RWF</p>
          <Button className="w-full bg-blue-600 hover:bg-blue-700 py-6 text-lg rounded-xl" onClick={() => window.location.reload()}>
            New Payment
          </Button>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <header className="h-16 bg-white border-b px-8 flex items-center justify-between sticky top-0 z-10">
        <Link href="/" className="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
          <div className="w-6 h-6 bg-blue-600 rounded flex items-center justify-center text-white text-[10px] font-bold">e</div>
          <span className="font-bold text-blue-600">eFiche</span>
        </Link>
        <UserCircle className="text-gray-400 cursor-pointer" size={28} />
      </header>

      <main className="max-w-6xl mx-auto w-full p-8 flex-1">
        <div className="bg-white rounded-xl p-6 border shadow-sm mb-8 flex gap-12 font-medium">
          <div className="flex gap-2"><span className="text-gray-400">Patient Name:</span> <span>{invoice.patient_name}</span></div>
          <div className="flex gap-2"><span className="text-gray-400">Visit ID:</span> <span>{invoice.visit_id}</span></div>
        </div>

        <div className="grid grid-cols-12 gap-8 items-start">
          <Card className="col-span-8 border-none shadow-sm rounded-xl overflow-hidden">
            <CardContent className="p-0">
              <table className="w-full text-left">
                <thead className="bg-gray-50/50 text-gray-400 text-sm font-medium border-b">
                  <tr>
                    <th className="px-6 py-4">Item</th>
                    <th className="px-6 py-4">Qty</th>
                    <th className="px-6 py-4">Price</th>
                    <th className="px-6 py-4">Total</th>
                  </tr>
                </thead>
                <tbody className="divide-y text-gray-700">
                  {invoice.items.map((item) => (
                    <tr key={item.id} className="hover:bg-gray-50/30 transition-colors">
                      <td className="px-6 py-5 font-medium">{item.description}</td>
                      <td className="px-6 py-5">{item.qty}</td>
                      <td className="px-6 py-5 font-mono">{item.price.toLocaleString()}</td>
                      <td className="px-6 py-5 font-mono">{item.total.toLocaleString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <div className="p-6 border-t flex justify-end gap-4 items-center">
                <span className="text-gray-400 font-bold">Subtotal:</span>
                <span className="text-lg font-bold">{invoice.total_amount.toLocaleString()} RWF</span>
              </div>
            </CardContent>
          </Card>

          <div className="col-span-4 space-y-6">
            <Card className="border-none shadow-sm rounded-xl">
              <CardContent className="p-8 text-center bg-white rounded-xl">
                <p className="text-gray-400 text-sm font-bold mb-1 uppercase tracking-wider">Remaining Balance</p>
                <h2 className="text-4xl font-extrabold text-blue-600">
                  {calculateRemaining().toLocaleString()} RWF
                </h2>
              </CardContent>
            </Card>

            <Card className="border-none shadow-sm rounded-xl">
              <CardContent className="p-8 space-y-8">
                <div className="space-y-4">
                  <h3 className="font-bold text-gray-800 text-sm">Insurance Coverage</h3>
                  <RadioGroup value={insurance} onValueChange={setInsurance} className="space-y-3">
                    {['None', 'RSSB', 'MMI'].map((opt) => (
                      <div key={opt} className="flex items-center space-x-3 group">
                        <RadioGroupItem value={opt.toLowerCase()} id={opt} className="border-gray-300 text-blue-600" />
                        <Label htmlFor={opt} className="text-gray-600 font-medium group-hover:text-blue-600 cursor-pointer transition-colors">
                          {opt}
                        </Label>
                      </div>
                    ))}
                  </RadioGroup>
                </div>

                {!hasPendingMomo(invoice) ? (
                  <div className="space-y-3">
                    <Button variant="secondary" className="w-full h-14 rounded-xl flex gap-3" onClick={() => handlePay('cash')} disabled={processing}>
                      <Banknote size={20} className="text-gray-500" /> Cash Payment
                    </Button>
                    <Button className="w-full h-14 rounded-xl flex gap-3 text-white" onClick={() => handlePay('mobile_money')} disabled={processing}>
                      <CreditCard size={20} /> Mobile Money Payment
                    </Button>
                  </div>
                ) : (
                  <div className="bg-amber-50 rounded-xl p-8 text-center border border-amber-100">
                    <div className="flex justify-center mb-4">
                      <Loader2 className="animate-spin text-amber-500" size={32} />
                    </div>
                    <h4 className="font-bold text-amber-800 mb-1">Waiting for confirmation...</h4>
                    <p className="text-xs text-amber-700 font-medium opacity-80 mb-4">
                      Transaction ID: {pendingPayment?.transaction_id || '---'}
                    </p>
                    <p className="mt-4 text-[10px] text-amber-600 font-medium leading-relaxed">
                      This can take a few seconds to a minute.
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </main>
    </div>
  );
}
