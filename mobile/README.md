# 📱 Aplicación Móvil Android — Sindicato de Choferes

Aplicación Móvil Oficial desarrollada para **Android (APK)** utilizando la tecnología **Capacitor + Vue 3 + Vite**, con soporte nativo para **todos los actores del sistema** y **persistencia local para guardado de asistencias sin conexión a internet (modo offline)**.

---

## 🛠️ Requisitos Previos

1. **Node.js** (v18+)
2. **Android Studio** (con SDK de Android instalado)

---

## 🚀 Pasos para Generar y Compilar el Archivo APK (`.apk`)

### Paso 1: Instalar Dependencias
Desde la carpeta `mobile/`:
```bash
npm install
```

### Paso 2: Generar el Paquete Web Móvil
```bash
npm run build
```

### Paso 3: Inicializar la Plataforma Android con Capacitor
```bash
npx cap add android
```

### Paso 4: Sincronizar el Código Web con el Proyecto Android
```bash
npx cap sync
```

### Paso 5: Abrir en Android Studio y Compilar el APK
```bash
npx cap open android
```
En Android Studio:
1. Esperar que ejecute la sincronización de Gradle.
2. Ir al menú superior: **Build > Build Bundle(s) / APK(s) > Build APK(s)**.
3. El archivo `app-debug.apk` se generará en la ruta:
   `mobile/android/app/build/outputs/apk/debug/app-debug.apk`
4. Copiar e instalar el archivo `.apk` en cualquier teléfono celular Android.

---

## 📶 Configuración de IP Servidor en Red Local (Wi-Fi)

1. En la pantalla de Login o en **Ajustes ⚙️**, ingresa la IP local de tu computadora ejecutando el servidor backend:
   ```
   http://192.168.1.XX:8000/api
   ```
2. Iniciar el backend Laravel habilitando conexiones remotas:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

---

## 💾 Funcionamiento del Modo Offline (Persistencia Local de Asistencias)

- El inspector puede presionar **"💾 Guardar en Celular (Offline)"** en la parada cuando no tenga señal o prefiera guardar rápido.
- Las asistencias se almacenan en la memoria local del teléfono (`asistencias_offline_queue`).
- Al recuperar conexión o presionar **"🔄 Sincronizar Ahora"**, la aplicación transmite automáticamente las marcaciones acumuladas al backend Laravel (`POST /api/asistencias/guardar`).
