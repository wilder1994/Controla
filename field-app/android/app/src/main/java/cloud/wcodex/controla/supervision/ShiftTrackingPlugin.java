package cloud.wcodex.controla.supervision;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Build;
import androidx.core.content.ContextCompat;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "ShiftTracking")
public class ShiftTrackingPlugin extends Plugin {
    @PluginMethod
    public void start(PluginCall call) {
        String apiBase = call.getString("apiBase");
        String token = call.getString("token");
        if (apiBase == null || apiBase.isEmpty() || token == null || token.isEmpty()) {
            call.reject("Faltan apiBase o token");
            return;
        }

        if (Build.VERSION.SDK_INT >= 33
            && getActivity() != null
            && ContextCompat.checkSelfPermission(getContext(), Manifest.permission.POST_NOTIFICATIONS)
                != PackageManager.PERMISSION_GRANTED) {
            getActivity().requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS}, 4401);
        }

        ShiftTrackingStore.save(getContext(), apiBase.replaceAll("/+$", ""), token);
        Intent intent = new Intent(getContext(), ShiftTrackingService.class);
        ContextCompat.startForegroundService(getContext(), intent);
        JSObject ret = new JSObject();
        ret.put("ok", true);
        call.resolve(ret);
    }

    @PluginMethod
    public void stop(PluginCall call) {
        getContext().stopService(new Intent(getContext(), ShiftTrackingService.class));
        ShiftTrackingStore.clear(getContext());
        call.resolve();
    }

    @PluginMethod
    public void updateToken(PluginCall call) {
        String token = call.getString("token");
        if (token != null && !token.isEmpty()) {
            ShiftTrackingStore.saveToken(getContext(), token);
        }
        call.resolve();
    }
}
