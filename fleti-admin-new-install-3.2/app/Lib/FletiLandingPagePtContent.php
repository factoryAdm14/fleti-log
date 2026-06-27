<?php

namespace App\Lib;

class FletiLandingPagePtContent
{
    public static function intro(): array
    {
        return [
            'title' => 'É hora de transformar sua experiência de mobilidade',
            'sub_title' => 'Conecte-se ao futuro com a Fleti Log — a solução inteligente, sustentável e eficiente para corridas, entregas e logística urbana.',
        ];
    }

    public static function businessStatistics(): array
    {
        return [
            'total_download' => [
                'title' => '40K+',
                'content' => 'Downloads',
            ],
            'complete_ride' => [
                'title' => '20M+',
                'content' => 'Viagens concluídas',
            ],
            'happy_customer' => [
                'title' => '1M+',
                'content' => 'Clientes satisfeitos',
            ],
            'support' => [
                'title' => '24h',
                'content' => 'Suporte',
            ],
        ];
    }

    public static function ourSolutionsIntro(): array
    {
        return [
            'title' => 'Nossas **Soluções**',
            'sub_title' => 'Explore nossa solução dinâmica para o dia a dia.',
        ];
    }

    public static function ourSolutionsItems(): array
    {
        return [
            [
                'title' => 'Entrega de encomendas',
                'description' => 'Envie encomendas importantes ao destino certo, com tarifa personalizada.',
            ],
            [
                'title' => 'Compartilhamento de viagens',
                'description' => 'Solicite uma corrida até seu destino e defina a tarifa pelo aplicativo.',
            ],
            [
                'title' => 'Agendar viagem',
                'description' => 'Planeje sua próxima corrida com agendamento antecipado pelo aplicativo.',
            ],
        ];
    }

    public static function ourServicesItems(): array
    {
        return [
            [
                'tab_name' => 'Corrida imediata',
                'title' => 'Comece a rodar e ganhe **no seu ritmo**',
                'description' => '<p>Faça parte da comunidade de motoristas Fleti Log e transforme cada quilômetro em oportunidade com nosso sistema de solicitações em tempo real.</p><ul><li>Aceite corridas compatíveis com sua localização e disponibilidade com um toque.</li><li>Carro ou moto: você escolhe como quer trabalhar.</li><li>Acompanhe seus ganhos em tempo real, com repasses e recompensas após cada viagem.</li></ul>',
            ],
            [
                'tab_name' => 'Agendar viagem',
                'title' => 'Planeje corridas com o **agendamento** da Fleti Log.',
                'description' => '<p>Organize viagens com antecedência, alinhando sua agenda às oportunidades da plataforma.</p><ul><li>Agende corridas que combinam com sua rotina e preferências.</li><li>Tenha mais previsibilidade de ganhos ao longo do dia.</li><li>Combine flexibilidade e planejamento no mesmo aplicativo.</li></ul>',
            ],
        ];
    }

    public static function ourServicesIntro(): array
    {
        return [
            'title' => 'Nossos **Serviços**',
            'subtitle' => 'Descubra soluções inovadoras para facilitar seu dia a dia.',
        ];
    }

    public static function gallery(): array
    {
        return [
            'card_1' => [
                'title' => 'Viagem concluída **sem complicações**',
                'subtitle' => 'Conforto, segurança e satisfação em cada trajeto. Termine sua jornada com tranquilidade — com a Fleti Log.',
            ],
            'card_2' => [
                'title' => 'Compartilhe sua viagem',
                'subtitle' => 'A cada quilômetro, descubra algo novo — porque cada corrida abre portas para novas possibilidades.',
            ],
        ];
    }

    public static function earnMoney(): array
    {
        return [
            'title' => 'Ganhe dinheiro com a **Fleti Log**',
            'subtitle' => 'Explore oportunidades ilimitadas: transforme seu tempo, habilidade e dedicação em renda com nossa plataforma.',
        ];
    }

    public static function earnMoneyButtons(): array
    {
        return [
            'title' => 'Baixe o app do motorista',
            'subtitle' => 'Comece sua jornada de ganhos aqui',
        ];
    }

    public static function customerAppDownload(): array
    {
        return [
            'title' => 'Baixe o **app do cliente**',
            'subtitle' => 'Solicite corridas e entregas com praticidade, segurança e pagamento facilitado.',
        ];
    }

    public static function customerAppDownloadButtons(): array
    {
        return [
            'title' => 'Disponível para Android e iOS',
            'subtitle' => 'Escaneie o QR Code ou acesse a loja de aplicativos',
        ];
    }

    public static function testimonialIntro(): array
    {
        return [
            'title' => 'O que dizem nossos **clientes**',
            'subtitle' => 'Experiências reais de quem usa a Fleti Log no dia a dia.',
        ];
    }

    public static function newsletter(): array
    {
        return [
            'title' => 'Receba novidades e **atualizações**',
            'subtitle' => 'Assine nossa newsletter e fique por dentro das novidades da Fleti Log.',
        ];
    }

    public static function footer(): array
    {
        return [
            'title' => 'Conecte-se às nossas redes e acompanhe as novidades.',
        ];
    }

    public static function testimonials(): array
    {
        return [
            [
                'reviewer_name' => 'Lois Nila',
                'designation' => 'Executiva',
                'review' => '"Fleti Log: mobilidade confiável para o dia a dia!"',
                'rating' => '5',
            ],
            [
                'reviewer_name' => 'Mac Steven Moba',
                'designation' => 'Engenheiro',
                'review' => '"Fleti Log: viagens práticas para quem tem rotina corrida!"',
                'rating' => '4.9',
            ],
            [
                'reviewer_name' => 'Jenny Klath',
                'designation' => 'Médica',
                'review' => '"Fleti Log: deslocamentos seguros todos os dias!"',
                'rating' => '4.5',
            ],
            [
                'reviewer_name' => 'Sir Moba',
                'designation' => 'Empresário',
                'review' => '"Fleti Log: agilidade para os deslocamentos do negócio!"',
                'rating' => '5',
            ],
            [
                'reviewer_name' => 'Jhon Doe',
                'designation' => 'Estudante',
                'review' => '"Fleti Log: corridas simples e acessíveis para o estudante!"',
                'rating' => '5',
            ],
        ];
    }
}
