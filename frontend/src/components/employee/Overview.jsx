import React from 'react';
import { RefreshCw } from 'lucide-react';

export default function Overview({ stats, inventory, onRefresh }) {
  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <h2 className="text-2xl font-black text-[#2D3748] tracking-tight">Omborxona Bosh Sahifasi</h2>
        <button onClick={onRefresh} className="p-3 skeuo-btn active:scale-95 duration-100 text-gray-600">
          <RefreshCw size={16} />
        </button>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="p-6 skeuo-convex">
          <div className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Bugun qabul qilingan</div>
          <div className="text-2xl font-black text-green-600">{stats.todayReceived.toLocaleString()} dona / kg</div>
        </div>
        <div className="p-6 skeuo-convex">
          <div className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Bugun jo'natilgan</div>
          <div className="text-2xl font-black text-blue-600">{stats.todayDispatched.toLocaleString()} dona / kg</div>
        </div>
        <div className="p-6 skeuo-convex">
          <div className="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Ombordagi tovar turlari</div>
          <div className="text-2xl font-black text-[#2D3748]">{stats.totalItems} xil</div>
        </div>
      </div>

      {/* Inventory table */}
      <div className="skeuo-convex p-6">
        <h3 className="font-bold text-gray-800 text-sm mb-4">📦 Ombor Zaxiralari Holati</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-gray-300 text-gray-500 uppercase font-extrabold tracking-wider">
                <th className="py-3 px-4">Mahsulot</th>
                <th className="py-3 px-4">Joylashuv</th>
                <th className="py-3 px-4">Qoldiq Miqdor</th>
                <th className="py-3 px-4">Tizimdagi joriy qoldiq</th>
              </tr>
            </thead>
            <tbody>
              {inventory.map(item => (
                <tr key={item.id} className="border-b border-gray-200">
                  <td className="py-3 px-4 font-bold text-gray-800">{item.product_name}</td>
                  <td className="py-3 px-4"><span className="px-2 py-1 bg-gray-100 rounded font-mono text-xs">{item.location}</span></td>
                  <td className="py-3 px-4 font-black">{parseFloat(item.quantity).toLocaleString()} {item.unit_type}</td>
                  <td className="py-3 px-4 text-gray-400">{parseFloat(item.system_quantity).toLocaleString()} {item.unit_type}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
