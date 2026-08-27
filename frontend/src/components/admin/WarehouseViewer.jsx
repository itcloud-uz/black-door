import React from 'react';

export default function WarehouseViewer({ warehouseInventory, warehouseOperations }) {
  return (
    <div>
      <h2 className="text-2xl font-black text-[#2D3748] mb-8">Omborxona Operatsiyalari Jurnali</h2>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Warehouse stock placement */}
        <div className="skeuo-convex p-6">
          <h3 className="font-bold text-gray-800 text-sm mb-4">📦 Haqiqiy Tovar Joylashuvi (Inventory Map)</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="border-b border-gray-300 text-gray-500 uppercase font-extrabold tracking-wider">
                  <th className="py-3 px-4">Mahsulot</th>
                  <th className="py-3 px-4">Location (Tokcha / Sektor)</th>
                  <th className="py-3 px-4">Miqdor</th>
                  <th className="py-3 px-4">Oxirgi tekshiruv</th>
                </tr>
              </thead>
              <tbody>
                {warehouseInventory.map(item => (
                  <tr key={item.id} className="border-b border-gray-200">
                    <td className="py-3 px-4 font-bold">{item.product_name}</td>
                    <td className="py-3 px-4"><span className="px-2 py-1 bg-gray-100 rounded font-mono">{item.location}</span></td>
                    <td className="py-3 px-4 font-black">{parseFloat(item.quantity).toLocaleString()} {item.unit_type}</td>
                    <td className="py-3 px-4 text-gray-400">
                      {item.last_counted_at ? new Date(item.last_counted_at).toLocaleString() : '—'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Operations logs history */}
        <div className="skeuo-convex p-6">
          <h3 className="font-bold text-gray-800 text-sm mb-4">📜 Oxirgi Kirim/Chiqim Operatsiyalari</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="border-b border-gray-300 text-gray-500 uppercase font-extrabold tracking-wider">
                  <th className="py-3 px-4">Operatsiya</th>
                  <th className="py-3 px-4">Mahsulot</th>
                  <th className="py-3 px-4">Miqdor</th>
                  <th className="py-3 px-4">Xodim</th>
                  <th className="py-3 px-4">Sana</th>
                </tr>
              </thead>
              <tbody>
                {warehouseOperations.map(op => (
                  <tr key={op.id} className="border-b border-gray-200">
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 rounded font-bold text-[10px] ${
                        op.operation_type === 'receive' ? 'bg-green-100 text-green-800' :
                        op.operation_type === 'dispatch' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'
                      }`}>{op.operation_type}</span>
                    </td>
                    <td className="py-3 px-4 font-bold">{op.product_name}</td>
                    <td className="py-3 px-4 font-black">{parseFloat(op.quantity).toLocaleString()} {op.unit_type}</td>
                    <td className="py-3 px-4 text-gray-500">{op.operator_name}</td>
                    <td className="py-3 px-4 text-gray-400">{new Date(op.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
