package com.example.grupo5.aitherapp.activitysApp;

import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.Point;
import android.graphics.RadialGradient;
import android.graphics.Shader;
import org.osmdroid.views.MapView;
import org.osmdroid.views.overlay.Overlay;
import org.osmdroid.util.GeoPoint;
import org.osmdroid.views.Projection;

import java.util.ArrayList;
import java.util.List;

public class ContaminacionOverlay extends Overlay {

    private List<PuntoContaminacion> puntos = new ArrayList<>();
    private Paint paint;

    public ContaminacionOverlay() {
        paint = new Paint();
        // Dither ayuda a que el degradado se vea más suave
        paint.setDither(true);
    }

    public void setPuntos(List<PuntoContaminacion> nuevosPuntos) {
        this.puntos = nuevosPuntos;
    }

    @Override
    public void draw(Canvas canvas, MapView mapView, boolean shadow) {
        if (shadow) return;

        Projection projection = mapView.getProjection();
        Point screenPoint = new Point();

        // Radio de la "nube" de gas en píxeles.
        // Puedes aumentarlo si quieres que las manchas sean más grandes.
        float radioNube = 150.0f;

        for (PuntoContaminacion punto : puntos) {
            // Convertir Lat/Lon a píxeles
            GeoPoint geoPoint = new GeoPoint(punto.latitud, punto.longitud);
            projection.toPixels(geoPoint, screenPoint);

            // Obtenemos el color base (Rojo, Amarillo, Verde) según el nivel
            int colorBase = obtenerColorBase(punto.nivel);

            // Creamos un degradado: Del color base (centro) hacia transparente (borde)
            // Esto crea el efecto de "humo" o "gas"
            RadialGradient gradient = new RadialGradient(
                    screenPoint.x, screenPoint.y,
                    radioNube,
                    new int[]{colorBase, colorBase & 0x00FFFFFF}, // Color sólido -> Color transparente
                    null,
                    Shader.TileMode.CLAMP
            );

            paint.setShader(gradient);

            // Dibujamos el círculo con el degradado
            canvas.drawCircle(screenPoint.x, screenPoint.y, radioNube, paint);
        }
    }

    /**
     * Devuelve un color ARGB dependiendo del nivel de contaminación.
     * Usamos Alpha 200 (semi-transparente) para que se vea un poco el mapa debajo.
     */
    private int obtenerColorBase(double nivel) {
        // Nivel 0.0 - 0.3: Verde (Buena calidad)
        if (nivel < 0.3) {
            return 0xC800FF00; // 0xC8 es la transparencia (aprox 80%)
        }
        // Nivel 0.3 - 0.6: Amarillo (Moderada)
        else if (nivel < 0.6) {
            return 0xC8FFFF00;
        }
        // Nivel > 0.6: Rojo (Mala calidad)
        else {
            return 0xC8FF0000;
        }
    }

    public static class PuntoContaminacion {
        public double latitud;
        public double longitud;
        public double nivel;

        public PuntoContaminacion(double lat, double lon, double nivel) {
            this.latitud = lat;
            this.longitud = lon;
            this.nivel = nivel;
        }
    }
}