export type Product = {
    name: string;
    slug: string;
    description: string;
    featureRequestsCount?: number;
};

export type FeatureRequestStatus = {
    value: 'new' | 'planned' | 'in_progress' | 'done' | 'declined';
    label: string;
};

export type FeatureRequest = {
    id: number;
    title: string;
    description: string;
    status: FeatureRequestStatus;
    acceptsVotes: boolean;
    votesCount: number;
    hasVoted: boolean;
    authorName: string | null;
    createdAt: string | null;
    can: {
        vote: boolean;
        update: boolean;
    };
};

export type SimilarFeatureRequest = Pick<
    FeatureRequest,
    'id' | 'title' | 'status' | 'votesCount'
>;

export type SelectOption = {
    value: string;
    label: string;
};

export type FeatureRequestFilters = {
    status: string | null;
    sort: string;
};

export type Paginated<T> = {
    data: T[];
    links: {
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
};
