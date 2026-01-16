package com.example.grupo5.aitherapp.activitysApp;

import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.Point;
import android.graphics.RadialGradient;
import android.graphics.Shader;
import org.osmdroid.util.TileSystem; // Importante
import org.osmdroid.views.MapView;
import org.osmdroid.views.overlay.Overlay;
import org.osmdroid.util.GeoPoint;
import org.osmdroid.views.Projection;

import java.util.ArrayList;
import java.util.List;

public class ContaminacionOverlay extends Overlay {

    private List<PuntoContaminacion> puntos = new ArrayList<>();
    private Paint paint;

    // Radio REAL de la mancha de contaminación (en metros)
    // 500-800m es ideal para ver barrios afectados
    private static final double RADIO_EN_METROS = 650.0;

    public ContaminacionOverlay() {
        paint = new Paint();
        paint.setDither(true);
        paint.setAntiAlias(true);
        paint.setStyle(Paint.Style.FILL);
    }

    public void setPuntos(List<PuntoContaminacion> nuevosPuntos) {
        this.puntos = nuevosPuntos;
    }

    @Override
    public void draw(Canvas canvas, MapView mapView, boolean shadow) {
        if (shadow || puntos.isEmpty()) return;

        Projection projection = mapView.getProjection();
        Point screenPoint = new Point();

        // --- CÁLCULO PRECISO DEL RADIO ---
        // 1. Obtenemos la latitud del centro del mapa (la distorsión depende de esto)
        double latitudCentro = mapView.getMapCenter().getLatitude();
        // 2. Obtenemos el nivel de zoom actual (puede ser decimal, ej: 15.5)
        double zoomLevel = mapView.getZoomLevelDouble();

        // 3. Calculamos cuántos metros representa 1 píxel en este momento exacto
        double metrosPorPixel = TileSystem.GroundResolution(latitudCentro, zoomLevel);

        // 4. Convertimos nuestros metros deseados a píxeles
        float radioPixels = (float) (RADIO_EN_METROS / metrosPorPixel);

        // Limitamos el tamaño mínimo para que no desaparezca si te alejas al espacio
        if (radioPixels < 2) radioPixels = 2;

        for (PuntoContaminacion punto : puntos) {
            // Convertir Lat/Lon a píxeles en pantalla
            GeoPoint geoPoint = new GeoPoint(punto.latitud, punto.longitud);
            projection.toPixels(geoPoint, screenPoint);

            // Optimización: No dibujar si está fuera de la pantalla
            // Dejamos un margen del tamaño del radio para no cortar los bordes
            if (screenPoint.x < -radioPixels || screenPoint.y < -radioPixels ||
                    screenPoint.x > canvas.getWidth() + radioPixels ||
                    screenPoint.y > canvas.getHeight() + radioPixels) {
                continue;
            }

            // Configuramos el color y degradado
            int colorCentro = obtenerColorBase(punto.intensidad);
            int colorBorde = colorCentro & 0x00FFFFFF; // Mismo color pero transparente

            RadialGradient gradient = new RadialGradient(
                    screenPoint.x, screenPoint.y,
                    radioPixels,
                    colorCentro,
                    colorBorde,
                    Shader.TileMode.CLAMP
            );

            paint.setShader(gradient);

            // Dibujamos
            canvas.drawCircle(screenPoint.x, screenPoint.y, radioPixels, paint);
        }
    }

    private int obtenerColorBase(double intensidad) {
        // Usamos una transparencia del 40% (0x66) para que se mezclen bien las capas
        int alpha = 0x66;

        if (intensidad < 0.2) return Color(alpha, 0, 255, 255);      // Cian (Muy limpio)
        else if (intensidad < 0.4) return Color(alpha, 0, 255, 0);   // Verde (Bien)
        else if (intensidad < 0.6) return Color(alpha, 255, 255, 0); // Amarillo (Regular)
        else if (intensidad < 0.8) return Color(alpha, 255, 165, 0); // Naranja (Mal)
        else return Color(alpha, 255, 0, 0);                         // Rojo (Peligro)
    }

    private int Color(int a, int r, int g, int b) {
        return (a << 24) | (r << 16) | (g << 8) | b;
    }

    public static class PuntoContaminacion {
        public double latitud;
        public double longitud;
        public double intensidad;

        public PuntoContaminacion(double lat, double lon, double intensidad) {
            this.latitud = lat;
            this.longitud = lon;
            this.intensidad = intensidad;
        }
    }
}   