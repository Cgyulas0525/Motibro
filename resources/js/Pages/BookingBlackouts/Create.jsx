import BlackoutForm from './Form';

export default function Create() {
    return (
        <BlackoutForm
            title="Új szabadság időszak"
            header="Szabadság / Új"
            action={route('booking-blackouts.store')}
        />
    );
}
