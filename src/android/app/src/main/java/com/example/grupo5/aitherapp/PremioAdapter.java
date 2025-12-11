package com.example.grupo5.aitherapp;
import com.example.grupo5.aitherapp.pojos.PojoPremio;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.net.Uri;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.journeyapps.barcodescanner.BarcodeEncoder;
import com.google.zxing.BarcodeFormat;
import com.google.zxing.WriterException;

import java.io.File;
import java.io.FileOutputStream;
import java.util.List;


public class PremioAdapter extends RecyclerView.Adapter<PremioAdapter.PremioViewHolder> {

    private List<PojoPremio> premios;
    private Context context;
    private OnCoinsChangedListener coinsListener;

    public PremioAdapter(List<PojoPremio> premios, Context context) {
        this.premios = premios;
        this.context = context;
    }

    public interface OnCoinsChangedListener {
        void onCoinsChanged(int nuevoCoins);
    }

    public void setOnCoinsChangedListener(OnCoinsChangedListener listener) {
        this.coinsListener = listener;
    }

    @NonNull
    @Override
    public PremioViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_premio, parent, false);
        return new PremioViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull PremioViewHolder holder, int position) {
        PojoPremio premio = premios.get(position);
        holder.tvNombre.setText(premio.getNombre());
        holder.tvCoins.setText(premio.getCoins() + " coins");
        holder.imgPremio.setImageResource(premio.getImagenResId());

        holder.itemView.setOnClickListener(v -> {
            SharedPreferences prefs = context.getSharedPreferences("MiAppPrefs", Context.MODE_PRIVATE);
            int coinsUsuario = prefs.getInt("coinsUsuario", 0);
            String email = prefs.getString("emailUsuario", "correo_por_defecto@example.com");

            if (coinsUsuario >= premio.getCoins()) {
                // Descontar coins
                coinsUsuario -= premio.getCoins();
                prefs.edit().putInt("coinsUsuario", coinsUsuario).apply();

                // Actualizar UI mediante callback
                if (coinsListener != null) {
                    coinsListener.onCoinsChanged(coinsUsuario);
                }

                Toast.makeText(context, "Elemento canjeado", Toast.LENGTH_SHORT).show();

                // Enviar correo con QR solo si es "Viaje en Transporte Público"
                if (premio.getNombre().equals("Viaje en Transporte Público")) {
                    Bitmap qr = generarQR("ID_USUARIO_" + System.currentTimeMillis());
                    if (qr != null) {
                        enviarCorreoConQR(email, premio.getNombre(), qr);
                    } else {
                        enviarCorreoConfirmacion(email, premio.getNombre());
                    }
                } else {
                    // Para otros premios normales
                    enviarCorreoConfirmacion(email, premio.getNombre());
                }

            } else {
                Toast.makeText(context, "No tienes suficientes AithCoins", Toast.LENGTH_SHORT).show();
            }
        });

    }

    @Override
    public int getItemCount() {
        return premios.size();
    }

    static class PremioViewHolder extends RecyclerView.ViewHolder {
        TextView tvNombre, tvCoins;
        ImageView imgPremio;

        public PremioViewHolder(@NonNull View itemView) {
            super(itemView);
            tvNombre = itemView.findViewById(R.id.tvNombre);
            tvCoins = itemView.findViewById(R.id.tvCoins);
            imgPremio = itemView.findViewById(R.id.imgPremio);
        }
    }

    // Generar QR
    private Bitmap generarQR(String texto) {
        try {
            BarcodeEncoder barcodeEncoder = new BarcodeEncoder();
            return barcodeEncoder.encodeBitmap(texto, BarcodeFormat.QR_CODE, 400, 400);
        } catch (WriterException e) {
            e.printStackTrace();
            return null;
        }
    }

    // Enviar correo normal
    private void enviarCorreoConfirmacion(String email, String nombrePremio) {
        Intent intent = new Intent(Intent.ACTION_SEND);
        intent.setType("message/rfc822");
        intent.putExtra(Intent.EXTRA_EMAIL, new String[]{email});
        intent.putExtra(Intent.EXTRA_SUBJECT, "Confirmación de canje");
        intent.putExtra(Intent.EXTRA_TEXT, "Has canjeado el premio: " + nombrePremio);
        context.startActivity(Intent.createChooser(intent, "Enviar correo..."));
    }

    // Enviar correo con QR
    private void enviarCorreoConQR(String email, String nombrePremio, Bitmap qrBitmap) {
        try {
            File qrFile = new File(context.getCacheDir(), "qr.png");
            FileOutputStream fos = new FileOutputStream(qrFile);
            qrBitmap.compress(Bitmap.CompressFormat.PNG, 100, fos);
            fos.flush();
            fos.close();

            Intent intent = new Intent(Intent.ACTION_SEND);
            intent.setType("image/png");
            intent.putExtra(Intent.EXTRA_EMAIL, new String[]{email});
            intent.putExtra(Intent.EXTRA_SUBJECT, "Confirmación de canje");
            intent.putExtra(Intent.EXTRA_TEXT, "Has canjeado el premio: " + nombrePremio);
            intent.putExtra(Intent.EXTRA_STREAM, Uri.fromFile(qrFile));

            context.startActivity(Intent.createChooser(intent, "Enviar correo..."));

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
