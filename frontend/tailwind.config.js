/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        background: '#EEF2F7',
        surface: '#EEF2F7',
        accent: {
          steel: '#4A5568',
          green: '#28A745',
          red: '#DC3545',
          blue: '#007BFF'
        }
      }
    },
  },
  plugins: [],
}
