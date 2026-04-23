/** @type {import('next').NextConfig} */
const nextConfig = {
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: 'http://backend/api/:path*',
      },
    ]
  },
}

module.exports = nextConfig
