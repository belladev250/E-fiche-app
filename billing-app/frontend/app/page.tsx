"use client";

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { Card, CardContent, Button } from '../components/ui';
import { UserCircle, Search, ArrowRight, Activity } from 'lucide-react';

interface Visit {
  id: number;
  patient_name: string;
  visit_id: string;
  status: string;
  created_at: string;
}

export default function Dashboard() {
  const [visits, setVisits] = useState<Visit[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('/api/visits')
      .then(res => res.json())
      .then(data => {
        setVisits(data);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <header className="h-16 bg-white border-b px-8 flex items-center justify-between sticky top-0 z-10">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 bg-blue-600 rounded flex items-center justify-center text-white text-[10px] font-bold">e</div>
          <span className="font-bold text-blue-600">eFiche Billing Dashboard</span>
        </div>
        <UserCircle className="text-gray-400 cursor-pointer" size={28} />
      </header>

      <main className="max-w-6xl mx-auto w-full p-8 flex-1">
        <div className="flex justify-between items-end mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Patient Visits</h1>
            <p className="text-gray-500 mt-1">Manage billing and payments for active visits.</p>
          </div>
          <div className="flex gap-3">
             <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input 
                  type="text" 
                  placeholder="Search patient..." 
                  className="pl-10 pr-4 h-11 bg-white border rounded-xl w-64 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm"
                />
             </div>
             <Button className="h-11 px-6 rounded-xl font-bold">New Visit</Button>
          </div>
        </div>

        <Card className="border-none shadow-sm rounded-xl overflow-hidden">
          <CardContent className="p-0">
            <table className="w-full text-left">
              <thead className="bg-gray-50/50 text-gray-400 text-sm font-bold border-b uppercase tracking-wider">
                <tr>
                  <th className="px-6 py-4">Patient Info</th>
                  <th className="px-6 py-4">Visit ID</th>
                  <th className="px-6 py-4">Date</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y text-gray-700">
                {loading ? (
                  Array(3).fill(0).map((_, i) => (
                    <tr key={i} className="animate-pulse">
                      <td colSpan={5} className="px-6 py-6 h-20 bg-gray-50/20"></td>
                    </tr>
                  ))
                ) : visits.length > 0 ? (
                  visits.map((visit) => (
                    <tr key={visit.id} className="hover:bg-gray-50/30 transition-colors group">
                      <td className="px-6 py-5">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                            {visit.patient_name.charAt(0)}
                          </div>
                          <span className="font-bold text-gray-900">{visit.patient_name}</span>
                        </div>
                      </td>
                      <td className="px-6 py-5">
                        <span className="font-mono text-sm bg-gray-100 px-2 py-1 rounded text-gray-600 font-medium">{visit.visit_id}</span>
                      </td>
                      <td className="px-6 py-5 text-gray-500 text-sm font-medium">
                        {new Date(visit.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-5">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600">
                          <Activity size={12} /> {visit.status}
                        </span>
                      </td>
                      <td className="px-6 py-5 text-right">
                        <Link href={`/billing/${visit.id}`}>
                          <Button variant="default" className="rounded-xl h-10 px-5 flex gap-2 group-hover:scale-105 transition-transform">
                            View Bill <ArrowRight size={16} />
                          </Button>
                        </Link>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="px-6 py-12 text-center text-gray-400 font-medium">
                      No active visits found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </CardContent>
        </Card>
      </main>
    </div>
  );
}
