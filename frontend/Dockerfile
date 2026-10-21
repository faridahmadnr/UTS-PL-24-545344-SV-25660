# ==========================================
# Stage 1: Build the Vue.js frontend with Vite
# ==========================================
FROM node:20-alpine AS builder

WORKDIR /app

# Copy dependency manifests first for layer caching
COPY package*.json ./
RUN npm ci

# Copy application source code and build for production
COPY . .
RUN npm run build

# ==========================================
# Stage 2: Serve static files using lightweight Nginx
# ==========================================
FROM nginx:1.27-alpine AS runner

# Copy compiled static assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy custom Nginx configuration for Vue SPA routing
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
