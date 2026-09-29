import BlackoutForm from './Form';

export default function Edit({ blackout }) {
    return (
        <BlackoutForm
            title="Szabadság időszak szerkesztése"
            header="Szabadság / Szerkesztés"
            method="patch"
            action={route('booking-blackouts.update', blackout.id)}
            defaults={blackout}
        />
    );
}
