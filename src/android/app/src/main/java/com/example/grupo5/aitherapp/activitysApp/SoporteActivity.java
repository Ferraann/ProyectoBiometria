package com.example.grupo5.aitherapp.activitysApp;

import android.os.Bundle;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentActivity;
import androidx.viewpager2.adapter.FragmentStateAdapter;
import androidx.viewpager2.widget.ViewPager2;

import com.example.grupo5.aitherapp.R;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;

public class SoporteActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_soporte);

        // 1. Botón Atrás
        findViewById(R.id.btnAtrasSoporte).setOnClickListener(v -> finish());

        // 2. Referencias UI
        TabLayout tabLayout = findViewById(R.id.tabLayoutSoporte);
        ViewPager2 viewPager = findViewById(R.id.viewPagerSoporte);

        // 3. Configurar el Adaptador (Ver clase abajo)
        SoportePagerAdapter adapter = new SoportePagerAdapter(this);
        viewPager.setAdapter(adapter);

        // 4. Conectar las Pestañas con el Swipe (TabLayout + ViewPager2)
        new TabLayoutMediator(tabLayout, viewPager, (tab, position) -> {
            if (position == 0) {
                tab.setText("Guía Rápida");
            } else {
                tab.setText("Formulario");
            }
        }).attach();
    }

    // --- CLASE INTERNA PARA EL ADAPTADOR ---
    // Esto es lo que gestiona qué fragmento se muestra
    private class SoportePagerAdapter extends FragmentStateAdapter {

        public SoportePagerAdapter(@NonNull FragmentActivity fragmentActivity) {
            super(fragmentActivity);
        }

        @NonNull
        @Override
        public Fragment createFragment(int position) {
            // Si es la posición 0, muestra la Guía. Si es 1, muestra Formulario.
            if (position == 0) {
                return new GuiaRapidaFragment();
            } else {
                return new FormularioFragment();
            }
        }

        @Override
        public int getItemCount() {
            return 2; // Tenemos 2 pestañas
        }
    }
}