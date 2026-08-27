import React from 'react';
import { RefreshCw } from 'lucide-react';

export default function Overview({ stats, discrepancyList, lowStockList, onRefresh }) {
  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <h2 className="text-2xl font-black text-[#2D3748] tracking-tight">Umumiy Panel Boshqaruvi</h2>
        <button onClick={onRefresh} className="p-3 skeuo-btn active:scale-95 duration-100 text-gray-600">
          <RefreshCw size={16} />
        </button>
      </div>

      {/* Top Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="p-6 skeuo-convex">
          <div className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">UZS Kassa Balansi</div>
          <div className="text-2xl font-black text-[#2D3748]">{stats.totalUzs.toLocaleString()} UZS</div>
        </div>
        <div className="p-6 skeuo-convex">
          <div className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">USD Kassa Balansi</div>
          <div className="text-2xl font-black text-green-600">${stats.totalUsd.toLocaleString()}</div>
        </div>
        <div className="p-6 skeuo-convex">
          <div className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Faol Zavodlar</div>
          <div className="text-2xl font-black text-purple-600">{stats.activeFactories} ta</div>
        </div>
        <div className="p-6 skeuo-convex text-red-600">
          <div className="text-red-400 text-xs font-bold uppercase tracking-wider mb-2">Kamaygan Mahsulotlar</div>
          <div className="text-2xl font-black">{stats.lowStock} ta</div>
        </div>
      </div>

      {/* Alert Logs & Understock Summary */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="p-6 skeuo-convex">
          <h3 className="font-extrabold text-gray-800 text-sm mb-4">⚠️ Inventar Farqlari (Discrepancy alerts)</h3>
          {discrepancyList.length === 0 ? (
            <p className="text-sm text-gray-400 italic">Farqlar aniqlanmadi.</p>
          ) : (
            <ul className="flex flex-col gap-2">
              {discrepancyList.map((d, i) => (
                <li key={i} className="p-3 bg-red-50 text-red-600 text-xs rounded border border-red-100 flex justify-between">
                  <span><strong>{d.name}</strong></span>
                  <span>Tizim: {d.system_qty} | Ombor: {d.warehouse_qty}</span>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="p-6 skeuo-convex">
          <h3 className="font-extrabold text-gray-800 text-sm mb-4">🛑 Minimal Qoldiq Ogohlantirishlari</h3>
          {lowStockList.length === 0 ? (
            <p className="text-sm text-gray-400 italic">Barcha mahsulotlar yetarli miqdorda.</p>
          ) : (
            <ul className="flex flex-col gap-2">
              {lowStockList.map((d, i) => (
                <li key={i} className="p-3 bg-yellow-50 text-yellow-800 text-xs rounded border border-yellow-100 flex justify-between">
                  <span><strong>{d.name}</strong></span>
                  <span>Qoldiq: {parseFloat(d.quantity_in_stock)} {d.unit_type}</span>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}
