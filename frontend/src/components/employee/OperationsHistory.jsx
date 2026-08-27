import React from 'react';

export default function OperationsHistory({ operations }) {
  return (
    <div className="skeuo-convex p-6">
      <h2 className="text-xl font-black text-[#2D3748] mb-6">Omborxona Hujjatlar Tarixi</h2>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="border-b border-gray-300 text-gray-500 uppercase font-extrabold tracking-wider">
              <th className="py-3 px-4">Operatsiya</th>
              <th className="py-3 px-4">Mahsulot</th>
              <th className="py-3 px-4">Miqdori</th>
              <th className="py-3 px-4">From (Qayerdan)</th>
              <th className="py-3 px-4">To (Qayerga)</th>
              <th className="py-3 px-4">Izoh</th>
              <th className="py-3 px-4">Sana / Vaqt</th>
            </tr>
          </thead>
          <tbody>
            {operations.map(op => (
              <tr key={op.id} className="border-b border-gray-200 hover:bg-gray-50">
                <td className="py-3 px-4">
                  <span className={`px-2.5 py-1 rounded text-[10px] font-bold ${
                    op.operation_type === 'receive' ? 'bg-green-100 text-green-800' :
                    op.operation_type === 'dispatch' ? 'bg-blue-100 text-blue-800' :
                    op.operation_type === 'damage_report' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'
                  }`}>{op.operation_type}</span>
                </td>
                <td className="py-3 px-4 font-bold text-gray-800">{op.product_name}</td>
                <td className="py-3 px-4 font-black">{parseFloat(op.quantity).toLocaleString()} {op.unit_type}</td>
                <td className="py-3 px-4 font-mono text-gray-400">{op.from_location || '—'}</td>
                <td className="py-3 px-4 font-mono text-gray-400">{op.to_location || '—'}</td>
                <td className="py-3 px-4 text-gray-500">{op.notes || '—'}</td>
                <td className="py-3 px-4">{new Date(op.created_at).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
