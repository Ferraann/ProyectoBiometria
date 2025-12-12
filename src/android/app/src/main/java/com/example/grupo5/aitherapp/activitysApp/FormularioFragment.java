package com.example.grupo5.aitherapp.activitysApp;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.fragment.app.Fragment;

import com.example.grupo5.aitherapp.R;

public class FormularioFragment extends Fragment {
    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        // Aquí podrías añadir la lógica de los botones si quieres
        return inflater.inflate(R.layout.fragment_formulario, container, false);
    }
}